<?php
if (isset($_GET['fb2'])) {
	if ($_GET['fb2'] == '') {
		unset($_SESSION['fb2']);
	} else {
		$_SESSION['fb2'] = true;
	}
}
if (isset($_GET['ru'])) {
	if ($_GET['ru'] == '') {
		unset($_SESSION['ru']);
	} else {
		$_SESSION['ru'] = true;
	}
}
if (isset($_GET['q'])) {
	if ($_GET['q'] == '') {
		unset($_SESSION['search']);
	} else {
		$get = mb_strtolower($_GET['q']);
		$search = str_replace(' ', '&', $get);
		$_SESSION['search'] = $search;
	}
}

if (isset($_GET['aid'])) {
	if ($_GET['aid'] == '') {
		unset($_SESSION['filter_author']);
	} else {
		$_SESSION['filter_author'] = intval($_GET['aid']);
	}
}


if (isset($_GET['gid'])) {
	if ($_GET['gid'] == '') {
		unset($_SESSION['filter_genre']);
	} else {
		$_SESSION['filter_genre'] = intval($_GET['gid']);
	}
}

if (isset($_GET['sid'])) {
	if ($_GET['sid'] == '') {
		unset($_SESSION['filter_series']);
	} else {
		$_SESSION['filter_series'] = intval($_GET['sid']);
	}
}



if (isset($_GET['xgid'])) {
	if ($_GET['xgid'] == '') {
		unset($_SESSION['filter_xgenre']);
	} else {
		if ($_SESSION['filter_genre'] == intval($_GET['xgid'])) {
			unset($_SESSION['filter_genre']);
		}
		$_SESSION['filter_xgenre'] = intval($_GET['xgid']);
	}
}


$filter = '';
$fcontent = '';
$join = '';
$cols = '';


// Кнопки форматов и языков
$fcontent .= '<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-3 pt-3 border-top">';
$fcontent .= '<div class="d-flex flex-wrap align-items-center gap-2">';

if (isset($_SESSION['fb2'])) {
	$filter .= "AND filetype='fb2' ";
	$fcontent .= "<a class='filter-chip active' href='$webroot/?fb2'><i class='fas fa-check me-1'></i> Только FB2</a> ";
} else {
	$fcontent .= "<a class='filter-chip' href='$webroot/?fb2=1'>Все форматы</a> ";
}

if (isset($_SESSION['ru'])) {
	$filter .= "AND lang='ru' ";
	$fcontent .= "<a class='filter-chip active' href='$webroot/?ru'><i class='fas fa-check me-1'></i> На русском</a> ";
} else {
	$fcontent .= "<a class='filter-chip' href='$webroot/?ru=1'>Все языки</a> ";
}
$fcontent .= '</div>';

// Переключатель вида (Сетка / Список)
$fcontent .= '<div class="btn-group view-mode-toggle ms-auto" role="group" aria-label="Режим отображения">';
$fcontent .= '<button type="button" class="btn btn-outline-secondary btn-sm active" id="view-mode-grid" title="Сетка карточек"><i class="fas fa-th-large"></i></button>';
$fcontent .= '<button type="button" class="btn btn-outline-secondary btn-sm" id="view-mode-list" title="Список"><i class="fas fa-list"></i></button>';
$fcontent .= '</div>';
$fcontent .= '</div>';

// Активные фильтры (Автор, Жанр, Серия, Поиск)
$active_filters_html = '';

if (isset($_SESSION['filter_author'])) {
	$do_cnt = true;
	$filter .= 'AND avtorid=:aid ';
	$join .= 'LEFT JOIN libavtor a USING(BookId) ';
	$stmt = $dbh->prepare("SELECT * FROM libavtorname LEFT JOIN libapics USING(AvtorId) WHERE AvtorId=:id");
	$stmt->bindParam(":id", $_SESSION['filter_author']);
	$stmt->execute();
	$a = $stmt->fetch();

	$active_filters_html .= "<a class='badge bg-primary-subtle text-primary border border-primary-subtle p-2 text-decoration-none rounded-pill me-1 mb-1 d-inline-flex align-items-center' href='$webroot/?aid'><i class='fas fa-user-edit me-1'></i> $a->lastname $a->firstname $a->middlename <i class='fas fa-times-circle ms-2'></i></a> ";
}

if (isset($_SESSION['filter_genre'])) {
	$filter .= 'AND g.genreid=:gid ';
	$join .= 'LEFT JOIN libgenre g USING(BookId) ';
	$stmt = $dbh->prepare("SELECT * FROM libgenrelist WHERE genreid=:id");
	$stmt->bindParam(":id", $_SESSION['filter_genre']);
	$stmt->execute();
	$g = $stmt->fetch();

	$active_filters_html .= "<a class='badge bg-success-subtle text-success border border-success-subtle p-2 text-decoration-none rounded-pill me-1 mb-1 d-inline-flex align-items-center' href='$webroot/?xgid=$g->genreid'><i class='fas fa-tag me-1'></i> $g->genremeta: $g->genredesc <i class='fas fa-times-circle ms-2'></i></a> ";
}

if (isset($_SESSION['filter_xgenre'])) {
	$filter .= 'AND (SELECT COUNT(*) FROM libgenre xg WHERE xg.BookId=B.BookId AND xg.genreid=:xgid) = 0';
	$stmt = $dbh->prepare("SELECT * FROM libgenrelist WHERE genreid=:id");
	$stmt->bindParam(":id", $_SESSION['filter_xgenre']);
	$stmt->execute();
	$xg = $stmt->fetch();

	$active_filters_html .= "<a class='badge bg-secondary-subtle text-secondary border border-secondary-subtle p-2 text-decoration-none rounded-pill me-1 mb-1 d-inline-flex align-items-center text-decoration-line-through' href='$webroot/?xgid'><i class='fas fa-ban me-1'></i> $xg->genremeta: $xg->genredesc <i class='fas fa-times-circle ms-2'></i></a> ";
}

if (isset($_SESSION['filter_series'])) {
	$do_cnt = true;
	$cols = 's.seqnumb,';
	$filter .= 'AND seqid=:sid ';
	$join .= 'LEFT JOIN libseq s USING(BookId) ';
	$stmt = $dbh->prepare("SELECT * FROM libseqname WHERE seqid=:id");
	$stmt->bindParam(":id", $_SESSION['filter_series']);
	$stmt->execute();
	$s = $stmt->fetch();

	$active_filters_html .= "<a class='badge bg-danger-subtle text-danger border border-danger-subtle p-2 text-decoration-none rounded-pill me-1 mb-1 d-inline-flex align-items-center' href='$webroot/?sid'><i class='fas fa-layer-group me-1'></i> $s->seqname <i class='fas fa-times-circle ms-2'></i></a> ";
	$order = "s.seqnumb, $order";
	$seqname = $s->seqname;
	$seqid = $_SESSION['filter_series'];
}

if (isset($_SESSION['search'])) {
	$filter .= "AND vector @@ to_tsquery('russian', :search) ";
	$join .= 'LEFT JOIN libbook_ts USING(bookid) ';

	$active_filters_html .= "<a class='badge bg-info-subtle text-info-emphasis border border-info-subtle p-2 text-decoration-none rounded-pill me-1 mb-1 d-inline-flex align-items-center' href='$webroot/?q'><i class='fas fa-search me-1'></i> " . htmlspecialchars($_SESSION['search']) . " <i class='fas fa-times-circle ms-2'></i></a> ";
}

if (isset($_SESSION['filter_series'])) {
	$active_filters_html .= "<a class='btn btn-sm btn-outline-info rounded-pill ms-2' href='$webroot/?fav_seq=$seqid'><i class='fas fa-bookmark me-1'></i> Серию в избранное</a> ";
}

echo "<div class='search-filter-card'>";
echo "<form action='$webroot/' method='GET'>";
echo "<div class='search-input-group'>";
echo "<i class='fas fa-search text-muted ms-2 me-2'></i>";
$current_search = isset($_GET['q']) ? htmlspecialchars($_GET['q']) : (isset($_SESSION['search']) ? htmlspecialchars($_SESSION['search']) : '');
echo "<input name='q' type='text' class='form-control' placeholder='Поиск книг по названию, автору или серии...' value='$current_search' autocomplete='off'>";
if (!empty($current_search)) {
	echo "<a href='$webroot/?q' class='btn btn-sm btn-link text-muted' title='Очистить'><i class='fas fa-times'></i></a>";
}
echo "<button type='submit' class='btn btn-primary rounded-pill px-4 ms-2'>Искать</button>";
echo "</div>";
echo "</form>";

if (!empty($active_filters_html)) {
	echo "<div class='d-flex flex-wrap align-items-center gap-1 mt-3'>$active_filters_html</div>";
}

echo $fcontent;
echo "</div>"; // search-filter-card


$sql = "SELECT *, $cols
        (SELECT Body FROM libbannotations WHERE BookId=b.BookId LIMIT 1) Body
		FROM libbook b
		$join
		WHERE deleted='0'
		$filter
		ORDER BY $order LIMIT " . RECORDS_PAGE . " OFFSET $start";

$stmt = $dbh->prepare($sql);

if (isset($_SESSION['filter_author'])) {
	$stmt->bindParam(":aid", $_SESSION['filter_author']);
}
if (isset($_SESSION['filter_genre'])) {
	$stmt->bindParam(":gid", $_SESSION['filter_genre']);
}
if (isset($_SESSION['filter_xgenre'])) {
	$stmt->bindParam(":xgid", $_SESSION['filter_xgenre']);
}
if (isset($_SESSION['filter_series'])) {
	$stmt->bindParam(":sid", $_SESSION['filter_series']);
}
if (isset($_SESSION['search'])) {
	$stmt->bindParam(":search", $_SESSION['search']);
}

try {
	$stmt->execute();
} catch (Exception $e) {
	$protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');
	header($protocol . ' 504 Gateway Time-out');

	echo "<div class='card m-3 border-danger shadow-sm'><div class='card-header bg-danger text-white fw-bold'>Ошибка базы данных</div><div class='card-body'>";
	echo "<h5>" . htmlspecialchars($e->getMessage()) . "</h5>";
	echo "<p class='text-muted'>Попробуйте упростить параметры поиска или убрать часть фильтров.</p>";
	echo "</div></div>";
}

if (COUNT_BOOKS) {
	$sql = "SELECT COUNT(*) cnt
		FROM libbook b
		$join
		WHERE deleted='0'
		$filter";
	$stt = $dbh->prepare($sql);

	if (isset($_SESSION['filter_author'])) {
		$stt->bindParam(":aid", $_SESSION['filter_author']);
	}
	if (isset($_SESSION['filter_genre'])) {
		$stt->bindParam(":gid", $_SESSION['filter_genre']);
	}
	if (isset($_SESSION['filter_xgenre'])) {
		$stt->bindParam(":xgid", $_SESSION['filter_xgenre']);
	}
	if (isset($_SESSION['filter_series'])) {
		$stt->bindParam(":sid", $_SESSION['filter_series']);
	}
	if (isset($_SESSION['search'])) {
		$stt->bindParam(":search", $_SESSION['search']);
	}

	$stt->execute();
	$cnt = $stt->fetch()->cnt;
	echo "<div class='d-flex justify-content-between align-items-center mb-3'>";
	echo "<span class='badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-2'><i class='fas fa-books me-1'></i> Найдено: " . number_format($cnt, 0, '.', ' ') . "</span>";
	echo "</div>";
} else {
	$cnt = 2000;
}

$rcnt = $stmt->rowCount();
if ($rcnt < RECORDS_PAGE) {
	$cnt = $page * RECORDS_PAGE + $rcnt;
}

show_gpager(ceil($cnt / RECORDS_PAGE), 5);

echo "<div id='books-catalog-container' class='books-grid'>";
while ($book = $stmt->fetch()) {
	book_info_pg($book, $webroot);
}
echo "</div>";

show_gpager(ceil($cnt / RECORDS_PAGE), 5);


