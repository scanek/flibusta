<style>
.c {
	background: #eee;
	border-radius: 50%;
	border-color: #eee;
}
</style>

<?php

$filter2 = "";
$letter = 'А%';
$get = '';

if (isset($_GET['q'])) {
	$get = mb_strtolower($_GET['q']);
	$letter = '%' . $get;
	$_SESSION['series_letter'] = $get;
}

if (isset($_SESSION['series_letter'])) {
	$get = $_SESSION['series_letter'];
}
if (isset($_GET['letter'])) {
	$get = mb_strtolower($_GET['letter']);
}
if ($get != '') {
	$_SESSION['series_letter'] = $get;
	$letter = $get . "%";
} else {
	unset($_SESSION['series_letter']);
}

echo "<div class='search-filter-card mb-4'>";
echo "<ul class='pagination pagination-sm flex-wrap gap-1 mb-2 justify-content-center'>";
	foreach (range(chr(0xC0), chr(0xDF)) as $b) {
		$l = iconv('CP1251', 'UTF-8', $b);
		$cc = ($l == mb_strtoupper($get)) ? 'active fw-bold' : '';
		echo "<li class='page-item $cc'><a class='page-link rounded-pill px-2' href='$webroot/series/?letter=" . urlencode($l) . "'>$l</a></li>";
	}
echo "</ul>";
echo "<ul class='pagination pagination-sm flex-wrap gap-1 mb-3 justify-content-center'>";
	foreach (range('A', 'Z') as $b) {
		$l = iconv('CP1251', 'UTF-8', $b);
		$cc = ($l == mb_strtoupper($get)) ? 'active fw-bold' : '';
		echo "<li class='page-item $cc'><a class='page-link rounded-pill px-2' href='$webroot/series/?letter=" . urlencode($l) . "'>$l</a></li>";
	}
echo "</ul>";

echo "<form action='$webroot/series/' method='GET'>\n";
?>
<div class="search-input-group">
  <i class="fas fa-search text-muted ms-2 me-2"></i>
  <input name="q" type="text" class="form-control" placeholder="Поиск серии по названию..." value="<?php echo htmlspecialchars($get); ?>">
  <?php if (!empty($get)) { echo "<a href='$webroot/series/' class='btn btn-sm btn-link text-muted' title='Сбросить'><i class='fas fa-times'></i></a>"; } ?>
  <button type='submit' class="btn btn-primary rounded-pill px-4 ms-2">Найти</button>
</div>
</form>
</div>

<?php
$start = SERIES_PAGE * $page;

$stmt = $dbh->prepare("SELECT COUNT(*) cnt FROM libseqname WHERE lower(libseqname.SeqName) LIKE :letter");
$stmt->bindParam(":letter", $letter);
$stmt->execute();
$cnt = $stmt->fetch()->cnt;

$stmt = $dbh->prepare("SELECT SeqName, SeqId,
		(SELECT COUNT(*) FROM libseq WHERE libseq.SeqId=libseqname.SeqId) cnt
		FROM libseqname 
		WHERE LOWER(libseqname.SeqName) LIKE :letter
		ORDER BY seqname LIMIT " . SERIES_PAGE . " OFFSET $start");
$stmt->bindParam(":letter", $letter);
$stmt->execute();

echo "<div class='d-flex justify-content-between align-items-center mb-3'>";
echo "<span class='badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-2'><i class='fas fa-layer-group me-1'></i> Найдено серий: " . number_format($cnt, 0, '.', ' ') . "</span>";
echo "</div>";

show_gpager(ceil($cnt / SERIES_PAGE), 5);
echo '<div class="row g-3">';
while ($bs = $stmt->fetch()) {
	if ($bs->cnt > 0) {
		echo "<div class='col-sm-6 col-lg-4'>";
		echo "<div class='card border-0 shadow-sm rounded-3 p-2 h-100 d-flex flex-row align-items-center justify-content-between'>";
		echo "<a class='text-decoration-none text-body fw-medium text-truncate me-2' href='$webroot/?sid=$bs->seqid' title='" . htmlspecialchars($bs->seqname) . "'>";
		echo "<i class='fas fa-bookmark me-2 text-danger opacity-75'></i>" . htmlspecialchars($bs->seqname) . "</a>";
		echo "<span class='badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill ms-auto'>$bs->cnt</span>";
		echo "</div>";
		echo "</div>";
	}
}
echo "</div>";

show_gpager(ceil($cnt / SERIES_PAGE), 5);

