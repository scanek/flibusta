<?php
$stmt = $dbh->prepare("SELECT COUNT(*) cnt FROM fav_users");
$stmt->execute();
$fav_count = $stmt->fetch()->cnt;

if ($fav_count == 0) {
	die("Книжные полки не определены");
}

echo "<div class='d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom'>";
echo "<h2 class='fw-bold mb-0'><i class='fas fa-bookmark text-primary me-2'></i> Моя книжная полка</h2>";
echo "<a href='$webroot/favlist/' class='btn btn-outline-primary btn-sm rounded-pill'><i class='fas fa-users me-1'></i> Сменить читателя</a>";
echo "</div>";

// Избранные авторы
$stmt = $dbh->prepare("SELECT *
		FROM fav
		LEFT JOIN libavtorname USING(AvtorId)
		LEFT JOIN libapics USING(AvtorId)
		WHERE user_uuid=:uuid AND avtorid IS NOT NULL");
$stmt->bindParam(":uuid", $user_uuid);

try {
	$stmt->execute();
	$fav_authors = $stmt->fetchAll();
	if (!empty($fav_authors)) {
		echo "<div class='card border-0 shadow-sm rounded-4 p-3 mb-4'>";
		echo "<h5 class='fw-bold mb-3 text-muted'><i class='fas fa-user-edit me-2'></i> Избранные авторы</h5>";
		echo "<div class='d-flex flex-wrap gap-2'>";
		foreach ($fav_authors as $a) {
			$name = trim("$a->lastname $a->firstname $a->middlename $a->nickname");
			if (empty($name)) $name = 'Неизвестный автор';
			echo "<a class='badge bg-primary-subtle text-primary border border-primary-subtle p-2 text-decoration-none rounded-pill d-inline-flex align-items-center' href='$webroot/author/view/$a->avtorid'>";
			if (!empty($a->file)) {
				echo "<img class='rounded-circle me-2' style='width: 24px; height: 24px; object-fit: cover;' src='$webroot/extract_author.php?id=$a->avtorid' />";	
			} else {
				echo "<i class='fas fa-user-circle me-1 text-muted'></i>";
			}
			echo htmlspecialchars($name) . "</a>";
		}
		echo "</div></div>";
	}
} catch (PDOException $e) {
	//
}

// Избранные серии
$stmt = $dbh->prepare("SELECT *
		FROM fav
		LEFT JOIN libseqname USING(seqid)
		WHERE user_uuid=:uuid AND seqid IS NOT NULL");
$stmt->bindParam(":uuid", $user_uuid);

try {
	$stmt->execute();
	$fav_series = $stmt->fetchAll();
	if (!empty($fav_series)) {
		echo "<div class='card border-0 shadow-sm rounded-4 p-3 mb-4'>";
		echo "<h5 class='fw-bold mb-3 text-muted'><i class='fas fa-layer-group me-2'></i> Избранные серии</h5>";
		echo "<div class='d-flex flex-wrap gap-2'>";
		foreach ($fav_series as $s) {
			echo "<a class='badge bg-danger-subtle text-danger border border-danger-subtle p-2 text-decoration-none rounded-pill' href='$webroot/?sid=$s->seqid'>";
			echo "<i class='fas fa-bookmark me-1'></i> " . htmlspecialchars($s->seqname) . "</a>";
		}
		echo "</div></div>";
	}
} catch (PDOException $e) {
	//
}

// Книги на полке
$stmt = $dbh->prepare("SELECT DISTINCT b.*
		FROM fav f
		LEFT JOIN libbook b USING(bookid)
		WHERE user_uuid=:uuid AND f.bookid IS NOT NULL");
$stmt->bindParam(":uuid", $user_uuid);
$stmt->execute();
$fav_books = $stmt->fetchAll();

echo "<h4 class='fw-bold mb-3 text-body'><i class='fas fa-book me-2 text-primary'></i> Книги на полке (" . count($fav_books) . ")</h4>";

if (!empty($fav_books)) {
	echo "<div id='books-catalog-container' class='books-grid'>";
	foreach ($fav_books as $book) {
		book_info_pg($book, $webroot);
	}
	echo "</div>";
} else {
	echo "<div class='card border-0 shadow-sm rounded-4 text-center p-5 text-muted'>";
	echo "<i class='far fa-bookmark fa-3x mb-3 text-primary opacity-50'></i>";
	echo "<h5>Ваша книжная полка пуста</h5>";
	echo "<p class='mb-3'>Добавляйте понравившиеся книги, нажимая на иконку сердечка в каталоге.</p>";
	echo "<div><a href='$webroot/' class='btn btn-primary rounded-pill px-4'>Перейти в каталог</a></div>";
	echo "</div>";
}

