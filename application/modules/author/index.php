<?php
include_once(ROOT_PATH . "webroot.php");
$stmt = $dbh->prepare("SELECT * FROM libavtorname LEFT JOIN libapics USING(AvtorId) WHERE avtorid=:id");
$stmt->bindParam(":id", $url->var1);
$stmt->execute();
$a = $stmt->fetch();

$author_name = trim("$a->lastname $a->firstname $a->middlename $a->nickname");
if (empty($author_name)) $author_name = 'Неизвестный автор';

echo "<div class='card border-0 shadow-sm rounded-4 p-4 mb-4'>";
echo "<div class='row g-4 align-items-start'>";

echo "<div class='col-md-3 text-center'>";
if (!empty($a->file)) {
	echo "<img src='$webroot/extract_author.php?id=$a->avtorid' class='rounded-circle shadow-sm mb-3' style='width: 140px; height: 140px; object-fit: cover;' alt='$author_name' />";	
} else {
	echo "<div class='rounded-circle bg-body-secondary text-muted d-inline-flex align-items-center justify-content-center shadow-sm mb-3' style='width: 140px; height: 140px;'><i class='fas fa-user fa-4x'></i></div>";
}
echo "<div class='d-grid gap-2'>";
echo "<a class='btn btn-primary rounded-pill' href='$webroot/?aid=$a->avtorid'><i class='fas fa-book me-1'></i> Книги автора</a>";

try {
	$stmt = $dbh->prepare("SELECT COUNT(*) cnt FROM fav WHERE user_uuid=:uuid AND avtorid=:id");
	$stmt->bindParam(":uuid", $user_uuid);
	$stmt->bindParam(":id", $a->avtorid);
	$stmt->execute();
	$is_fav = ($stmt->fetch()->cnt > 0);
	if (!$is_fav) {
		echo "<a class='btn btn-outline-secondary rounded-pill' href='$webroot/?fav_author=$a->avtorid'><i class='far fa-heart me-1'></i> В избранное</a>";
	} else {
		echo "<a class='btn btn-danger rounded-pill' href='$webroot/?unfav_author=$a->avtorid'><i class='fas fa-heart me-1'></i> На полке</a>";
	}
} catch (PDOException $e) {
	//
}
echo "</div>"; // d-grid
echo "</div>"; // col-md-3

echo "<div class='col-md-9'>";
echo "<h2 class='fw-bold mb-3' style='font-family: var(--font-serif);'>$author_name</h2>";

$stmt = $dbh->prepare("SELECT * FROM libaannotations WHERE AvtorId=:id");
$stmt->bindParam(":id", $url->var1);
$stmt->execute();
$has_bio = false;
while ($an = $stmt->fetch()) {
	$has_bio = true;
	if (!empty($an->title)) {
		echo "<h5 class='text-muted mb-2'>" . htmlspecialchars($an->title) . "</h5>";
	}
	echo "<div class='text-body-secondary lh-lg mb-3'>" . bbc2html(nl2br($an->body)) . "</div>";
}

if (!$has_bio) {
	echo "<p class='text-muted'>Информация и биография автора пока не добавлены.</p>";
}

echo "</div>"; // col-md-9
echo "</div></div>"; // row, card

