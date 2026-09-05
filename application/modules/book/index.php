<?php
echo "<script>var url = '$webroot/usr.php?id=$url->var1';</script>";

function nl2p($string) {
    $paragraphs = '';

    foreach (explode("\n", $string) as $line) {
        if (trim($line)) {
            $paragraphs .= '<p>' . $line . '</p>';
        }
    }

    return $paragraphs;
}
book_info_pg($book, $webroot, true);

// Отзывы читателей
$stmt_rev = $dbh->prepare("SELECT name, text, time FROM libreviews WHERE bookid=:id ORDER BY time DESC");
$stmt_rev->bindParam(":id", $url->var1);
$stmt_rev->execute();
$reviews = $stmt_rev->fetchAll();

if (!empty($reviews)) {
	echo "<div class='card border-0 shadow-sm rounded-4 p-4 mb-4'>";
	echo "<h4 class='fw-bold mb-3'><i class='far fa-comments me-2 text-primary'></i> Отзывы читателей (" . count($reviews) . ")</h4>";
	echo "<div class='d-flex flex-column gap-3'>";
	foreach ($reviews as $r) {
		$r_name = htmlspecialchars($r->name ?? 'Анонимный читатель');
		$r_text = nl2br(htmlspecialchars(stripslashes($r->text ?? '')));
		echo "<div class='p-3 rounded-3 bg-body-tertiary border'>";
		echo "<div class='d-flex align-items-center gap-2 mb-2'>";
		echo "<div class='rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center' style='width: 32px; height: 32px;'><i class='fas fa-user'></i></div>";
		echo "<span class='fw-bold'>$r_name</span>";
		echo "</div>";
		echo "<p class='mb-0 text-body-secondary'>$r_text</p>";
		echo "</div>";
	}
	echo "</div></div>";
}

function str_replace_first($from, $to, $content) { 
    $from = '/'.preg_quote($from, '/').'/';
    return preg_replace($from, $to, $content, 1);
}

$ext = strtolower(trim($book->filetype ?? ''));
$book_id = intval($url->var1);
$usr = ($ext == 'fb2') ? 0 : 1;

$stmt = $dbh->prepare("SELECT filename FROM book_zip WHERE :id BETWEEN start_id AND end_id AND usr=:usr LIMIT 1");
$stmt->bindValue(':id', $book_id, PDO::PARAM_INT);
$stmt->bindValue(':usr', $usr, PDO::PARAM_INT);
$stmt->execute();
$zip_row = $stmt->fetch();
$zip_name = $zip_row ? $zip_row->filename : '';
$zip = new ZipArchive(); 

echo "<div id='reader' class='mt-4'>";
echo "<div class='reader-progress-container'><div id='reader-progress-bar' class='reader-progress-bar'></div></div>";

if ($zip_name && $zip->open(BOOKS_PATH . $zip_name)) {
	echo "<div id='reader-wrapper' class='reader-wrapper reader-paper shadow-sm'>";

	// Панель управления ридером
	echo "<div class='reader-toolbar'>";
	echo "<div class='d-flex align-items-center gap-2'>";
	echo "<span class='fw-bold text-truncate' style='max-width: 260px;'><i class='fas fa-book-open me-1 text-primary'></i> " . htmlspecialchars($book->title ?? '') . "</span>";
	echo "<span id='reader-progress-val' class='badge bg-secondary-subtle text-secondary rounded-pill'>0%</span>";
	echo "</div>";

	echo "<div class='d-flex flex-wrap align-items-center gap-2 ms-auto'>";

	// Размер шрифта
	echo "<div class='btn-group btn-group-sm' role='group' aria-label='Размер шрифта'>";
	echo "<button id='reader-font-dec' type='button' class='btn btn-outline-secondary' title='Уменьшить шрифт'>A-</button>";
	echo "<span id='reader-font-size-val' class='btn btn-outline-secondary disabled fw-bold'>18px</span>";
	echo "<button id='reader-font-inc' type='button' class='btn btn-outline-secondary' title='Увеличить шрифт'>A+</button>";
	echo "</div>";

	// Гарнитура шрифта
	echo "<select id='reader-font-select' class='form-select form-select-sm' style='width: auto;' aria-label='Выбор шрифта'>";
	echo "<option value='serif'>Книжный (Literata)</option>";
	echo "<option value='sans'>Экранный (Inter)</option>";
	echo "</select>";

	// Цветовые темы ридера
	echo "<div class='d-flex align-items-center gap-1 ms-1' title='Цветовая схема ридера'>";
	echo "<button type='button' class='reader-theme-btn reader-paper active' data-rtheme='paper' title='Бумага'></button>";
	echo "<button type='button' class='reader-theme-btn reader-sepia' data-rtheme='sepia' title='Сепия'></button>";
	echo "<button type='button' class='reader-theme-btn reader-night' data-rtheme='night' title='Ночь'></button>";
	echo "<button type='button' class='reader-theme-btn reader-oled' data-rtheme='oled' title='OLED Чёрный'></button>";
	echo "</div>";

	// Полноэкранный режим
	echo "<button id='reader-fullscreen-btn' type='button' class='btn btn-outline-secondary btn-sm ms-1' title='На весь экран'><i class='fas fa-expand'></i></button>";

	echo "</div>"; // d-flex
	echo "</div>"; // reader-toolbar

	// Контент книги
	echo "<div id='reader-content' class='reader-content'>";

	if ($ext == 'fb2') {
		include('fb.php');
	} elseif ($ext == 'txt') {
		include('txt.php');
	} elseif ($ext == 'epub') {
		include('epub.php');
	} elseif ($ext == 'pdf') {
		include('pdf.php');
	} elseif ($ext == 'mobi') {
		include('mobi.php');
	} elseif (($ext == 'djvu') || ($ext == 'djv')) {
		include('djvu.php');
	} elseif ($ext == 'rtf') {
		include('rtf.php');
	} elseif ($ext == 'docx') {
		include('docx.php');
	} elseif (($ext == 'html') || ($ext == 'htm')) {
		include('html.php');
	}

	echo "</div>"; // reader-content
	echo "</div>"; // reader-wrapper

	$zip->close();
} else {
	echo "<div class='alert alert-info d-flex align-items-center rounded-4 shadow-sm p-4 my-4'>";
	echo "<i class='fas fa-info-circle fa-2x me-3 text-primary'></i>";
	echo "<div><h5>Архив с книгой не найден на диске</h5>";
	echo "<p class='mb-0 text-muted'>Файл книги ещё не загружен в папку библиотеки. Вы можете скачать его через функцию обновления архивов.</p></div>";
	echo "</div>";
}

echo "</div>"; // reader
