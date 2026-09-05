<?php

function bbc2html($content) {
  $search = array (
    '/(\[b\])(.*?)(\[\/b\])/',
    '/(\[i\])(.*?)(\[\/i\])/',
    '/(\[u\])(.*?)(\[\/u\])/',
    '/(\[ul\])(.*?)(\[\/ul\])/',
    '/(\[li\])(.*?)(\[\/li\])/',
    '/(\[url=)(.*?)(\])(.*?)(\[\/url\])/',
    '/(\[url\])(.*?)(\[\/url\])/'
  );

  $replace = array (
    '<strong>$2</strong>',
    '<em>$2</em>',
    '<u>$2</u>',
    '<ul>$2</ul>',
    '<li>$2</li>',
    '<a href="$2" target="_blank">$4</a>',
    '<a href="$2" target="_blank">$2</a>'
  );

  return preg_replace($search, $replace, $content);
}


function show_gpager($page_count, $block_size = 5) {
	global $url;
	if (isset($_GET['page'])) {
		$page = intval($_GET['page']);
	} else {
		$page = 0;
	}
	if ($page_count > 1) {
		echo "<nav aria-label='Навигация по страницам' class='my-4'>";
		echo "<ul class='pagination pagination-sm justify-content-center flex-wrap gap-1'>";

		$b1 = max(1, $page - $block_size + 1);
		$b2 = min($page_count, $page + $block_size + 1);

		if ($page > 0) {
			echo "<li class='page-item'><a class='page-link rounded-pill px-3' href='?page=0' title='Первая'><i class='fas fa-angle-double-left'></i></a></li>";
			echo "<li class='page-item'><a class='page-link rounded-pill px-3' href='?page=" . ($page - 1) . "' title='Назад'><i class='fas fa-angle-left'></i></a></li>";
		}

		for ($p = $b1; $p <= $b2; $p++) {
			$pv = ($p == $page + 1) ? 'active fw-bold' : '';
			echo "<li class='page-item $pv'><a class='page-link rounded-pill px-3' href='?page=" . ($p - 1) . "'>$p</a></li>";
		}

		if ($page + 1 < $page_count) {
			echo "<li class='page-item'><a class='page-link rounded-pill px-3' href='?page=" . ($page + 1) . "' title='Вперёд'><i class='fas fa-angle-right'></i></a></li>";
			echo "<li class='page-item'><a class='page-link rounded-pill px-3' href='?page=" . ($page_count - 1) . "' title='Последняя'><i class='fas fa-angle-double-right'></i></a></li>";
		}

		echo '</ul></nav>';
	}
}

function render_book_cover($book, $webroot = '', $size = 'small') {
	$book_id = intval($book->bookid ?? 0);
	$color_idx = $book_id % 6;
	$title = htmlspecialchars($book->title ?? 'Без названия', ENT_QUOTES);
	$type = strtoupper(htmlspecialchars($book->filetype ?? 'FB2', ENT_QUOTES));
	$img_url = "$webroot/extract_cover.php?id=$book_id" . ($size == 'small' ? '&small' : '');

	$html = "<img class='book-img' src='$img_url' alt='$title' loading='lazy' onerror=\"this.style.display='none'; var f=this.nextElementSibling; if(f) f.style.display='flex';\" />";
	$html .= "<div class='cover-fallback cover-fallback-$color_idx' style='display:none;'><span class='fallback-badge'>$type</span><div class='fallback-title'>$title</div></div>";
	return $html;
}

function book_small_pg($book, $webroot = '', $full = false) {
	book_info_pg($book, $webroot, false);
}

function book_info_pg($book, $webroot = '', $full = false) {
	global $dbh, $user_uuid;
	if (!isset($book->bookid)) {
		return;
	}

	$book_id = intval($book->bookid);
	$title = htmlspecialchars($book->title ?? 'Без названия');
	$ft = trim(strtolower($book->filetype ?? 'fb2'));
	$fhref = ($ft === 'fb2') ? "$webroot/fb2.php?id=$book_id" : "$webroot/usr.php?id=$book_id";
	$year = ($book->year != 0) ? $book->year : (DateTime::createFromFormat('Y-m-d H:i:se', $book->time ?? '') ? DateTime::createFromFormat('Y-m-d H:i:se', $book->time)->format('Y') : '');

	// Избранное
	$fav_btn = '';
	if (!empty($user_uuid)) {
		$stmt_fav = $dbh->prepare("SELECT COUNT(*) cnt FROM fav WHERE user_uuid=:uuid AND bookid=:id");
		$stmt_fav->bindParam(":uuid", $user_uuid);
		$stmt_fav->bindParam(":id", $book_id);
		$stmt_fav->execute();
		if ($stmt_fav->fetch()->cnt > 0) {
			$fav_btn = "<a href='?unfav_book=$book_id' title='Удалить с полки' class='btn btn-danger btn-sm'><i class='fas fa-heart'></i></a>";
		} else {
			$fav_btn = "<a href='?fav_book=$book_id' title='Добавить на полку' class='btn btn-outline-secondary btn-sm'><i class='far fa-heart'></i></a>";
		}
	}

	// Авторы
	$stmt_a = $dbh->prepare("SELECT AvtorId, LastName, FirstName, nickname, middlename, File FROM libavtor a
		LEFT JOIN libavtorname USING(AvtorId)
		LEFT JOIN libapics USING(AvtorId)
		WHERE a.BookId=:id");
	$stmt_a->bindParam(":id", $book_id);
	$stmt_a->execute();
	$authors = $stmt_a->fetchAll();

	$authors_names = [];
	$authors_html = '';
	foreach ($authors as $a) {
		$name = trim("$a->lastname $a->firstname $a->middlename $a->nickname");
		if (empty($name)) $name = 'Неизвестный автор';
		$authors_names[] = htmlspecialchars($name);
		$avatar = !empty($a->file) ? "<img class='rounded-circle me-1' style='width: 20px; height: 20px; object-fit: cover;' src='$webroot/extract_author.php?id=$a->avtorid' />" : "<i class='fas fa-user-circle me-1 text-muted'></i>";
		$authors_html .= "<a class='badge bg-body-secondary text-body-secondary text-decoration-none border me-1 mb-1 p-1 d-inline-flex align-items-center' href='$webroot/author/view/$a->avtorid'>$avatar" . htmlspecialchars($name) . "</a> ";
	}
	$authors_short = implode(', ', $authors_names);

	// Жанры
	$stmt_g = $dbh->prepare("SELECT GenreId, GenreDesc FROM libgenre JOIN libgenrelist USING(GenreId) WHERE BookId=:id");
	$stmt_g->bindParam(":id", $book_id);
	$stmt_g->execute();
	$genres_html = '';
	while ($g = $stmt_g->fetch()) {
		$genres_html .= "<a class='badge-genre me-1 mb-1 d-inline-block' href='$webroot/?gid=$g->genreid'>" . htmlspecialchars($g->genredesc) . "</a> ";
	}

	// Серии
	$stmt_s = $dbh->prepare("SELECT SeqId, SeqName, SeqNumb FROM libseq JOIN libseqname USING(SeqId) WHERE BookId=:id");
	$stmt_s->bindParam(":id", $book_id);
	$stmt_s->execute();
	$seq_html = '';
	while ($s = $stmt_s->fetch()) {
		$numb = ($s->seqnumb > 0) ? " #" . $s->seqnumb : "";
		$seq_html .= "<a class='badge-series me-1 mb-1 d-inline-block' href='$webroot/?sid=$s->seqid'>" . htmlspecialchars($s->seqname) . "$numb</a> ";
	}

	// Аннотация
	$body_text = isset($book->body) ? trim($book->body) : '';
	$body_clean = strip_tags($body_text);

	// ПОЛНЫЙ ВИД (Страница произведения /book/view/<id>)
	if ($full) {
		$cover_img = render_book_cover($book, $webroot, 'large');
		echo "<div class='book-detail-hero' itemscope itemtype='http://schema.org/Book'>";
		echo "<div class='row g-4'>";
		echo "<div class='col-md-4 col-lg-3 text-center text-md-start'>";
		echo "<div class='cover-wrapper mx-auto mx-md-0' style='max-width: 240px; aspect-ratio: 2/3; border-radius: var(--radius-md); overflow: hidden; box-shadow: var(--shadow-cover); position: relative;'>";
		echo $cover_img;
		echo "</div>";
		echo "</div>";

		echo "<div class='col-md-8 col-lg-9'>";
		echo "<h1 class='book-detail-title'>$title</h1>";
		echo "<div class='mb-3'>$authors_html</div>";
		echo "<div class='mb-3 d-flex flex-wrap align-items-center gap-1'>$genres_html $seq_html</div>";

		echo "<div class='d-flex flex-wrap align-items-center gap-2 my-4'>";
		echo "<a href='#reader' class='btn btn-primary btn-lg rounded-pill px-4 shadow-sm'><i class='fas fa-book-open me-2'></i> Читать онлайн</a>";
		echo "<a href='$fhref' class='btn btn-outline-primary btn-lg rounded-pill px-4'><i class='fas fa-download me-2'></i> Скачать " . strtoupper($ft) . "</a>";
		echo $fav_btn;
		echo "</div>";

		if (!empty($year)) {
			echo "<p class='text-muted mb-2'><i class='far fa-calendar-alt me-1'></i> Год издания: <strong>$year</strong> &bull; Формат: <strong>" . strtoupper($ft) . "</strong></p>";
		}

		if (!empty($body_clean)) {
			echo "<div class='book-detail-annotation'>";
			echo "<h5 class='fw-bold mb-2'>Аннотация</h5>";
			echo "<p class='text-body-secondary' style='white-space: pre-line;'>" . nl2br(htmlspecialchars($body_clean)) . "</p>";
			echo "</div>";
		}

		echo "</div>"; // col-md-8
		echo "</div>"; // row
		echo "</div>\n"; // book-detail-hero
		return;
	}

	// КАТАЛОЖНЫЙ ВИД (Поддержка Сетки Grid и Списка List)
	$cover_img = render_book_cover($book, $webroot, 'small');
	$annotation_short = cut_str($body_clean, 220);

	echo "<div class='book-card' itemscope itemtype='http://schema.org/Book'>";
	echo "<div class='cover-wrapper'>";
	echo "<a href='$webroot/book/view/$book_id' class='cover-link' title='$title'>$cover_img</a>";
	echo "</div>";

	echo "<div class='book-body'>";
	echo "<a class='book-title' href='$webroot/book/view/$book_id' title='$title'>$title</a>";
	echo "<div class='book-author' title='$authors_short'>$authors_short</div>";

	if (!empty($seq_html)) {
		echo "<div class='book-series mb-1'>$seq_html</div>";
	}
	if (!empty($genres_html)) {
		echo "<div class='book-genres mb-1'>$genres_html</div>";
	}
	if (!empty($annotation_short)) {
		echo "<div class='book-annotation text-muted'>$annotation_short</div>";
	}

	echo "<div class='book-footer'>";
	echo "<div class='d-flex align-items-center gap-1'>";
	echo "<span class='badge bg-secondary-subtle text-secondary badge-format'>" . strtoupper($ft) . "</span>";
	if (!empty($year)) {
		echo "<span class='text-muted book-year ms-1' style='font-size: 0.75rem;'>$year</span>";
	}
	echo "</div>";

	echo "<div class='btn-group btn-group-sm'>";
	echo "<a href='$fhref' class='btn btn-outline-primary btn-sm' title='Скачать " . strtoupper($ft) . "'><i class='fas fa-download'></i></a>";
	echo $fav_btn;
	echo "</div>";
	echo "</div>"; // book-footer

	echo "</div>"; // book-body
	echo "</div>\n"; // book-card
}

date_default_timezone_set('Europe/Minsk');
date_default_timezone_set('Etc/GMT-3');
setlocale(LC_ALL, 'rus_RUS');

$m_time = explode(" ",microtime());
$m_time = $m_time[0] + $m_time[1];
$starttime = $m_time;
$sql_time = 0;


$cdt = date('Y-m-d H:i:s');
$today_from =  date('Y-m-d') . ' 00:00:00';
$today_to   = date('Y-m-d') . ' 23:59:59';


function russian_date() {
 $translation = array(
 "am" => "дп",
 "pm" => "пп",
 "AM" => "ДП",
 "PM" => "ПП",
 "Monday" => "Понедельник",
 "Mon" => "Пн",
 "Tuesday" => "Вторник",
 "Tue" => "Вт",
 "Wednesday" => "Среда",
 "Wed" => "Ср",
 "Thursday" => "Четверг",
 "Thu" => "Чт",
 "Friday" => "Пятница",
 "Fri" => "Пт",
 "Saturday" => "Суббота",
 "Sat" => "Сб",
 "Sunday" => "Воскресенье",
 "Sun" => "Вс",
 "January" => "Января",
 "Jan" => "Янв",
 "February" => "Февраля",
 "Feb" => "Фев",
 "March" => "Марта",
 "Mar" => "Мар",
 "April" => "Апреля",
 "Apr" => "Апр",
 "May" => "Мая",
 "May" => "Мая",
 "June" => "Июня",
 "Jun" => "Июн",
 "July" => "Июля",
 "Jul" => "Июл",
 "August" => "Августа",
 "Aug" => "Авг",
 "September" => "Сентября",
 "Sep" => "Сен",
 "October" => "Октября",
 "Oct" => "Окт",
 "November" => "Ноября",
 "Nov" => "Ноя",
 "December" => "Декабря",
 "Dec" => "Дек",
 "st" => "ое",
 "nd" => "ое",
 "rd" => "е",
 "th" => "ое",
 );
 if (func_num_args() > 1) {
	$timestamp = func_get_arg(1);
	return strtr(date(func_get_arg(0), $timestamp), $translation);
 } else {
	return strtr(date(func_get_arg(0)), $translation);
 };
}
/***************************************************************************/
function transliterate($string){
  $cyr=array(
     "Щ", "Ш", "Ч","Ц", "Ю", "Я", "Ж","А","Б","В",
     "Г","Д","Е","Ё","З","И","Й","К","Л","М","Н",
     "О","П","Р","С","Т","У","Ф","Х","Ь","Ы","Ъ",
     "Э","Є", "Ї","І",
     "щ", "ш", "ч","ц", "ю", "я", "ж","а","б","в",
     "г","д","е","ё","з","и","й","к","л","м","н",
     "о","п","р","с","т","у","ф","х","ь","ы","ъ",
     "э","є", "ї","і", " "
  );
  $lat=array(
     "Shch","Sh","Ch","C","Yu","Ya","J","A","B","V",
     "G","D","e","e","Z","I","y","K","L","M","N",
     "O","P","R","S","T","U","F","H","", 
     "Y","" ,"E","E","Yi","I",
     "shch","sh","ch","c","Yu","Ya","j","a","b","v",
     "g","d","e","e","z","i","y","k","l","m","n",
     "o","p","r","s","t","u","f","h",
     "", "y","" ,"e","e","yi","i", "%20"
  );
  for($i=0; $i<count($cyr); $i++)  {
     $c_cyr = $cyr[$i];
     $c_lat = $lat[$i];
     $string = str_replace($c_cyr, $c_lat, $string);
  }
  $string = 
  	preg_replace(
  		"/([qwrtpsdfghklzxcvbnmQWRTPSDFGHKLZXCVBNM]+)[jJ]e/", 
  		"\${1}e", $string);
/*  $string = 
  	preg_replace(
  		"/([qwrtpsdfghklzxcvbnmQWRTPSDFGHKLZXCVBNM]+)[jJ]/", 
  		"\${1}'", $string);*/
  $string = preg_replace("/([eyuioaEYUIOA]+)[Kk]h/", "\${1}h", $string);
  $string = preg_replace("/^kh/", "h", $string);
  $string = preg_replace("/^Kh/", "H", $string);
  return $string;
}


function stars($rating, $webroot) {
    $fullStar = '<img alt="1" class="star" src="'.$webroot.'/i/s1.png" />';
    $emptyStar = '<img alt="0" class="star" src="'.$webroot.'/i/s0.png" />';
    $rating = $rating <= 5?$rating:5;
    $fullStarCount = (int)$rating;
    $emptyStarCount = 5 - $fullStarCount;
    $html = str_repeat($fullStar,$fullStarCount);
    $html .= str_repeat($emptyStar,$emptyStarCount);
    echo $html;
}

/***************************************************************************/
function cut_str($string, $maxlen=700) {
    $len = (mb_strlen($string) > $maxlen)
        ? mb_strripos(mb_substr($string, 0, $maxlen), ' ')
        : $maxlen
    ;
    $cutStr = mb_substr($string, 0, $len);
    return (mb_strlen($string) > $maxlen)
        ? $cutStr . '...'
        : $cutStr
    ;
}

/***************************************************************************/
function cut_str2($string, $maxlen=700) {
    $len = (mb_strlen($string) > $maxlen)
        ? mb_strripos(mb_substr($string, 0, $maxlen), ' ')
        : $maxlen
    ;
    $cutStr = mb_substr($string, 0, $len);
    return $cutStr . $len;
}

/***************************************************************************/
function clean_str($input) {
  if (!$input)
	return $input;

  $input = strip_tags($input);

  $input = str_replace ("\n"," ", $input);
  $input = str_replace ("\r","", $input);

  $input = preg_replace("/[^(\w)|(\x7F-\xFF)|^(_,\-,\.,\;,\@)|(\s)]/", " ", $input);

  return $input;
}

/***************************************************************************/
function decode_gurl($webroot,$mobile = false)  {
  global $last_modified, $url, $robot;
  global $sex_post;

 
  $urlx = parse_url(urldecode($_SERVER['REQUEST_URI']));

  //remove leading webroot e.g. http://192.168.1.101/flibusta/authors/index.php should produce module= authors
  // note this assumes path is not utf-8
  $path = $urlx['path'];
  if (!empty($webroot) && str_starts_with($path,$webroot) ) {
		$path = substr($path, strlen($webroot));
  }
  list($x, $module, $action, $var1, $var2, $var3) = array_pad(explode('/', $path), 6, null);
  $url = new stdClass();

  $url->mod = safe_str($module);
  $url->action = safe_str($action);
  $url->var1 = intval($var1);
  $url->var2 = intval($var2);
  $url->var3 = intval($var3); 
  $url->title = '';
  $url->description = '';
  $url->mod_path = '';
  $url->mod_menu = '';
  $url->image = '';
  $url->noindex = 0;
  $url->index = 1;
  $url->follow = 1;
  $url->module_menu = '';
  $url->js = array();
  $url->editor = 0;
  $url->access = 0;
  $url->canonical = '';

  $menu = true;

  if ($url->mod == '') {
    $url->mod ='primary';
  }

  if (file_exists(ROOT_PATH . 'modules/' . $url->mod . '/module.conf')) {
    $last_modified = gmdate('D, d M Y H:i:s', filemtime(ROOT_PATH . 'modules/' . $url->mod . '/index.php')) . ' GMT';
    $url->module = ROOT_PATH . 'modules/' . $url->mod . '/index.php';
    $url->mod_path = ROOT_PATH . 'modules/' . $url->mod . '/';
    include(ROOT_PATH . 'modules/' . $url->mod . '/module.conf');
  } else {
    $menu = false;
    include(ROOT_PATH . 'modules/404/module.conf');
    $url->module = ROOT_PATH . 'modules/404/index.php';
    $url->mod = '404';  
  }

  if ($url->access > 0) {
   // if (!is_admin()) {
      include(ROOT_PATH . 'modules/403/module.conf');
      $url->module = ROOT_PATH . 'modules/403/index.php';
      $url->mod = '403';
      $menu = false;
   // }
  }

  if ( (file_exists(ROOT_PATH . 'modules/' . $url->mod . '/module_menu.php')) && ($menu) ) {
    $url->module_menu = ROOT_PATH . 'modules/' . $url->mod . '/module_menu.php';
  }

  return $url;
}

function safe_str($str) {
        return ($str)?preg_replace("/[^A-Za-z0-9 -_]/", '', $str):$str;
}


function mobile() {
        $devices = array(
                "android" => "android.*mobile",
                "androidtablet" => "android(?!.*mobile)",
                "iphone" => "(iphone|ipod)",
                "ipad" => "(ipad)",
                "generic" => "(kindle|mobile|mmp|midp|pocket|psp|symbian|smartphone|treo|up.browser|up.link|vodafone|wap|opera mini)"
        );
        $isMobile = false;
        if (isset($_SERVER['HTTP_USER_AGENT'])) {
                $userAgent = $_SERVER['HTTP_USER_AGENT'];
        } else {
                $userAgent = "";
        }
        if (isset($_SERVER['HTTP_ACCEPT'])) {
               $accept = $_SERVER['HTTP_ACCEPT'];
        } else {
                $accept = '';
        }
        if (isset($_SERVER['HTTP_X_WAP_PROFILE']) || isset($_SERVER['HTTP_PROFILE'])) {
                $isMobile = true;
        } elseif (strpos($accept, 'text/vnd.wap.wml') > 0 || strpos($accept, 'application/vnd.wap.xhtml+xml') > 0) {
                $isMobile = true;
        } else {
                foreach ($devices as $device => $regexp) {
                        if (preg_match("/" . $devices[strtolower($device)] . "/i", $userAgent)) {
                                $isMobile = true;
                        }
                }
        }
        return $isMobile;
}

function formatSizeUnits($bytes)
    {
        if ($bytes >= 1073741824)
        {
            $bytes = number_format($bytes / 1073741824, 2) . ' GB';
        }
        elseif ($bytes >= 1048576)
        {
            $bytes = number_format($bytes / 1048576, 2) . ' MB';
        }
        elseif ($bytes >= 1024)
        {
            $bytes = number_format($bytes / 1024, 2) . ' KB';
        }
        elseif ($bytes > 1)
        {
            $bytes = $bytes . ' bytes';
        }
        elseif ($bytes == 1)
        {
            $bytes = $bytes . ' byte';
        }
        else
        {
            $bytes = '0 bytes';
        }

        return $bytes;
    }

function get_book_mime_type($ft) {
	$ft = strtolower(trim($ft ?? ''));
	switch ($ft) {
		case 'fb2':
			return 'application/x-fictionbook+xml';
		case 'epub':
			return 'application/epub+zip';
		case 'mobi':
		case 'azw':
		case 'azw3':
			return 'application/x-mobipocket-ebook';
		case 'pdf':
			return 'application/pdf';
		case 'djvu':
			return 'image/vnd.djvu';
		case 'txt':
			return 'text/plain';
		case 'rtf':
			return 'application/rtf';
		case 'doc':
			return 'application/msword';
		case 'docx':
			return 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
		case 'cbr':
			return 'application/x-cbr';
		case 'cbz':
			return 'application/x-cbz';
		default:
			return 'application/octet-stream';
	}
}

function opds_book($b, $webroot = '') {
	global $dbh;
	$bookid = $b->bookid ?? $b->BookId ?? 0;
	$title = $b->title ?? $b->Title ?? 'Книга';
	$time = $b->time ?? $b->Time ?? date('Y-m-d H:i:s');
	$lang = trim($b->lang ?? $b->Lang ?? 'ru');
	$year = intval($b->year ?? $b->Year ?? 0);
	$filetype = strtolower(trim($b->filetype ?? $b->FileType ?? 'fb2'));
	$filesize = intval($b->filesize ?? $b->FileSize ?? 0);
	$keywords = trim($b->keywords ?? $b->Keywords ?? '');

	echo "\n<entry>\n";
	echo " <updated>" . htmlspecialchars($time, ENT_XML1, 'UTF-8') . "</updated>\n";
	echo " <id>tag:book:$bookid</id>\n";
	echo " <title>" . htmlspecialchars($title, ENT_XML1, 'UTF-8') . "</title>\n";

	$ann = $dbh->prepare("SELECT body annotation FROM libbannotations WHERE bookid=:id LIMIT 1");
	$ann->bindValue(":id", $bookid, PDO::PARAM_INT);
	$ann->execute();
	$an = '';
	if ($tmp = $ann->fetch()) {
		$an = $tmp->annotation ?? '';
	}

	$genres = $dbh->prepare("SELECT genrecode, GenreId, GenreDesc FROM libgenre 
		JOIN libgenrelist USING(GenreId)
		WHERE bookid=:id");
	$genres->bindValue(":id", $bookid, PDO::PARAM_INT);
	$genres->execute();
	while ($g = $genres->fetch()) {
		$gcode = $g->genrecode ?? $g->GenreCode ?? '';
		$gdesc = $g->genredesc ?? $g->GenreDesc ?? '';
		echo " <category term='" . htmlspecialchars($webroot . "/opds/list/?genre_id=" . ($g->genreid ?? $g->GenreId), ENT_QUOTES, 'UTF-8') . "' label='" . htmlspecialchars($gdesc, ENT_XML1, 'UTF-8') . "'/>\n";
	}

	$sq = '';
	$seq = $dbh->prepare("SELECT SeqId, SeqName, SeqNumb FROM libseq
		JOIN libseqname USING(SeqId)
		WHERE BookId=:id");
	$seq->bindValue(":id", $bookid, PDO::PARAM_INT);
	$seq->execute();
	while ($s = $seq->fetch()) {
		$sid = $s->seqid ?? $s->SeqId;
		$sname = $s->seqname ?? $s->SeqName;
		$snumb = intval($s->seqnumb ?? $s->SeqNumb ?? 0);
		$ssq = $sname;
		if ($snumb > 0) {
			$ssq .= " ($snumb)";
		}
		$sq .= ($sq ? ', ' : '') . $ssq;
		echo " <link href='" . htmlspecialchars($webroot . "/opds/list?seq_id=" . $sid, ENT_QUOTES, 'UTF-8') . "' rel='related' type='application/atom+xml;profile=opds-catalog' title='" . htmlspecialchars("Все книги серии \"$ssq\"", ENT_QUOTES, 'UTF-8') . "' />\n";
	}
	if ($sq != '') {
		$sq = "Серия: $sq";
	}

	$au = $dbh->prepare("SELECT AvtorId, LastName, FirstName, nickname, middlename FROM libavtor a
		LEFT JOIN libavtorname USING(AvtorId)
		WHERE a.bookid=:id");
	$au->bindValue(":id", $bookid, PDO::PARAM_INT);
	$au->execute();
	$authors_list = $au->fetchAll();

	foreach ($authors_list as $a) {
		$aname = trim(($a->lastname ?? '') . ' ' . ($a->firstname ?? '') . ' ' . ($a->middlename ?? ''));
		if (empty($aname) && !empty($a->nickname)) {
			$aname = $a->nickname;
		}
		if (empty($aname)) {
			$aname = 'Неизвестный автор';
		}
		echo " <author>\n";
		echo "  <name>" . htmlspecialchars($aname, ENT_XML1, 'UTF-8') . "</name>\n";
		echo "  <uri>" . htmlspecialchars($webroot . "/opds/author?author_id=" . ($a->avtorid ?? $a->AvtorId), ENT_QUOTES, 'UTF-8') . "</uri>\n";
		echo " </author>\n";
		echo " <link href='" . htmlspecialchars($webroot . "/opds/list?author_id=" . ($a->avtorid ?? $a->AvtorId), ENT_QUOTES, 'UTF-8') . "' rel='related' type='application/atom+xml;profile=opds-catalog' title='" . htmlspecialchars("Все книги автора $aname", ENT_QUOTES, 'UTF-8') . "' />\n";
	}

	if ($lang != '') {
		echo " <dc:language>" . htmlspecialchars($lang, ENT_XML1, 'UTF-8') . "</dc:language>\n";
	}
	if ($year > 0) {
		echo " <dc:issued>$year</dc:issued>\n";
	}
	if ($filetype != '') {
		echo " <dc:format>" . htmlspecialchars($filetype, ENT_XML1, 'UTF-8') . "</dc:format>\n";
	}
	if ($filesize > 0) {
		echo " <dcterms:extent>" . formatSizeUnits($filesize) . "</dcterms:extent>\n";
	}

	$clean_summary = trim(strip_tags($an));
	$summary_text = $clean_summary;
	if ($sq != '') {
		$summary_text .= ($summary_text ? "\n\n" : "") . $sq;
	}
	if ($keywords != '') {
		$summary_text .= ($summary_text ? "\n" : "") . "Теги: $keywords";
	}
	if ($year > 0) {
		$summary_text .= ($summary_text ? "\n" : "") . "Год: $year";
	}
	if ($filesize > 0) {
		$summary_text .= ($summary_text ? "\n" : "") . "Размер: " . formatSizeUnits($filesize);
	}
	echo " <summary type='text'>" . htmlspecialchars($summary_text, ENT_XML1, 'UTF-8') . "</summary>\n";

	echo " <link rel='http://opds-spec.org/image/thumbnail' href='" . htmlspecialchars($webroot . "/extract_cover.php?id=" . $bookid, ENT_QUOTES, 'UTF-8') . "' type='image/jpeg'/>\n";
	echo " <link rel='http://opds-spec.org/image' href='" . htmlspecialchars($webroot . "/extract_cover.php?id=" . $bookid, ENT_QUOTES, 'UTF-8') . "' type='image/jpeg'/>\n";

	$ur = ($filetype === 'fb2') ? 'fb2' : 'usr';
	$mime = get_book_mime_type($filetype);
	$dl_title = "Скачать " . strtoupper($filetype);
	echo " <link href='" . htmlspecialchars($webroot . "/$ur.php?id=" . $bookid, ENT_QUOTES, 'UTF-8') . "' rel='http://opds-spec.org/acquisition/open-access' type='$mime' title='" . htmlspecialchars($dl_title, ENT_QUOTES, 'UTF-8') . "' />\n";

	if ($filetype === 'fb2') {
		echo " <link href='" . htmlspecialchars($webroot . "/$ur.php?id=" . $bookid, ENT_QUOTES, 'UTF-8') . "' rel='http://opds-spec.org/acquisition/open-access' type='application/fb2+zip' title='Скачать FB2.ZIP' />\n";
	}

	echo " <link href='" . htmlspecialchars($webroot . "/book/view/" . $bookid, ENT_QUOTES, 'UTF-8') . "' rel='alternate' type='text/html' title='Книга на сайте' />\n";
	echo "</entry>\n";
}
