<?php
$stmt = $dbh->prepare("SELECT g.GenreMeta
	FROM libgenrelist g
	GROUP BY g.GenreMeta
	ORDER BY (SELECT COUNT(*) FROM libgenrelist a WHERE g.GenreMeta=a.GenreMeta) DESC");
$stmt->execute();

echo "<div class='row g-4'>";

while ($bg = $stmt->fetch()) {
	$meta_title = htmlspecialchars($bg->genremeta ?? 'Жанры');

	echo "<div class='col-md-6'>";
	echo "<div class='card border-0 shadow-sm rounded-4 h-100'>";
	echo "<div class='card-header bg-transparent border-bottom py-3'>";
	echo "<h4 class='fw-bold mb-0 text-primary'><i class='fas fa-tags me-2'></i> $meta_title</h4>";
	echo "</div>";

	echo "<div class='card-body p-3'>";

	$st2 = $dbh->prepare("SELECT libgenrelist.genreid, libgenrelist.genremeta, libgenrelist.genredesc,
		(SELECT COUNT(*) FROM libgenre WHERE libgenre.GenreId=libgenrelist.GenreId) cnt
		FROM libgenrelist
		WHERE GenreMeta=:meta
		ORDER BY genredesc");
	$st2->bindParam(":meta", $bg->genremeta);
	$st2->execute();
	while ($g = $st2->fetch()) {	
		$desc = htmlspecialchars($g->genredesc);
		echo "<div class='d-flex align-items-center justify-content-between py-2 border-bottom border-light-subtle'>";
		echo "<div class='d-flex align-items-center gap-2 text-truncate me-2'>";
		echo "<a class='btn btn-outline-primary btn-sm rounded-pill text-truncate' href='$webroot/?gid=$g->genreid'>$desc</a> ";
		echo "<a class='btn btn-outline-danger btn-sm rounded-circle p-0 text-center' style='width: 24px; height: 24px; line-height: 22px;' href='$webroot/?xgid=$g->genreid' title='Исключить жанр'><i class='fas fa-times' style='font-size: 10px;'></i></a>";
		echo "</div>";
		echo "<span class='badge bg-body-secondary text-body-secondary rounded-pill'>$g->cnt</span>";
		echo "</div>";
	}
	
	echo "</div>";
	echo "</div>";
	echo "</div>";
}
echo "</div>";
