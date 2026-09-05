<style>
.c {
	background: #eee;
	border-radius: 50%;
	border-color: #eee;
}
</style>

<?php

include_once(ROOT_PATH . "webroot.php");
$filter2 = "";
$letter = 'А%';
$get = '';

if (isset($_GET['q'])) {
	$get = mb_strtolower($_GET['q']);
	$letter = '%' . $get;
	$_SESSION['authors_letter'] = $get;
}

if (isset($_SESSION['authors_letter'])) {
	$get = $_SESSION['authors_letter'];
}
if (isset($_GET['letter'])) {
	$get = mb_strtolower($_GET['letter']);
}
if ($get != '') {
	$_SESSION['authors_letter'] = $get;
	$letter = $get . "%";
} else {
	unset($_SESSION['series_letter']);
}

echo "<div class='search-filter-card mb-4'>";
echo "<ul class='pagination pagination-sm flex-wrap gap-1 mb-2 justify-content-center'>";
	foreach (range(chr(0xC0), chr(0xDF)) as $b) {
		$l = iconv('CP1251', 'UTF-8', $b);
		$cc = ($l == mb_strtoupper($get)) ? 'active fw-bold' : '';
		echo "<li class='page-item $cc'><a class='page-link rounded-pill px-2' href='$webroot/authors/?letter=" . urlencode($l) . "'>$l</a></li>";
	}
echo "</ul>";
echo "<ul class='pagination pagination-sm flex-wrap gap-1 mb-3 justify-content-center'>";
	foreach (range('A', 'Z') as $b) {
		$l = iconv('CP1251', 'UTF-8', $b);
		$cc = ($l == mb_strtoupper($get)) ? 'active fw-bold' : '';
		echo "<li class='page-item $cc'><a class='page-link rounded-pill px-2' href='$webroot/authors/?letter=" . urlencode($l) . "'>$l</a></li>";
	}
echo "</ul>";

echo "<form action='$webroot/authors/' method='GET'>\n";
?>
<div class="search-input-group">
  <i class="fas fa-search text-muted ms-2 me-2"></i>
  <input name="q" type="text" class="form-control" placeholder="Поиск автора по фамилии или имени..." value="<?php echo htmlspecialchars($get); ?>">
  <?php if (!empty($get)) { echo "<a href='$webroot/authors/' class='btn btn-sm btn-link text-muted' title='Сбросить'><i class='fas fa-times'></i></a>"; } ?>
  <button type='submit' class="btn btn-primary rounded-pill px-4 ms-2">Найти</button>
</div>
</form>
</div>

<?php
$start = AUTHORS_PAGE * $page;

$stmt = $dbh->prepare("SELECT COUNT(*) cnt FROM libavtorname WHERE lower(libavtorname.lastname) LIKE :letter");
$stmt->bindParam(":letter", $letter);
$stmt->execute();
$cnt = $stmt->fetch()->cnt;

$stmt = $dbh->prepare("SELECT *,
		(SELECT COUNT(*) FROM libavtor WHERE libavtor.avtorid=libavtorname.avtorid) cnt
		FROM libavtorname
		LEFT JOIN libapics USING(AvtorId)
		WHERE LOWER(libavtorname.lastname) LIKE :letter
		ORDER BY firstname LIMIT " . AUTHORS_PAGE . " OFFSET $start");
$stmt->bindParam(":letter", $letter);
$stmt->execute();

echo "<div class='d-flex justify-content-between align-items-center mb-3'>";
echo "<span class='badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-2'><i class='fas fa-user-pen me-1'></i> Найдено авторов: " . number_format($cnt, 0, '.', ' ') . "</span>";
echo "</div>";

show_gpager(ceil($cnt / AUTHORS_PAGE), 5);
echo '<div class="row g-3">';
while ($a = $stmt->fetch()) {
	if ($a->cnt > 0) {
		$name = trim("$a->lastname $a->firstname $a->middlename $a->nickname");
		if (empty($name)) $name = 'Неизвестный автор';
		echo "<div class='col-sm-6 col-lg-4'>";
		echo "<div class='card border-0 shadow-sm rounded-3 p-2 h-100 d-flex flex-row align-items-center justify-content-between'>";
		echo "<a class='d-flex align-items-center text-decoration-none text-body fw-medium text-truncate' href='$webroot/author/view/$a->avtorid'>";
		if (!empty($a->file)) {
			echo "<img class='rounded-circle me-2 flex-shrink-0' style='width: 32px; height: 32px; object-fit: cover;' src='$webroot/extract_author.php?id=$a->avtorid' />";	
		} else {
			echo "<i class='fas fa-user-circle fa-2x me-2 text-muted flex-shrink-0'></i>";
		}
		echo "<span class='text-truncate'>" . htmlspecialchars($name) . "</span></a>";
		echo "<span class='badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill ms-2'>$a->cnt</span>";
		echo "</div>";
		echo "</div>";
	}
}
echo "</div>";

show_gpager(ceil($cnt / AUTHORS_PAGE), 5);
