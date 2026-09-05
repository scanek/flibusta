<?php
if (isset($_GET['id'])) {
	$id = intval($_GET['id']);
} else {
	die();
}
if ($id <= 0) {
	die();
}
error_reporting(E_ALL);
include('../init.php');

$stmt = $dbh->prepare("SELECT libbook.Title BookTitle, libfilename.filename, libbook.filetype,
	CONCAT(libavtorname.LastName, ' ', libavtorname.FirstName) author_name
		FROM libbook 
		LEFT JOIN libavtor USING(BookId) 
		LEFT JOIN libfilename USING(BookId) 
		LEFT JOIN libavtorname USING(AvtorId) 
		WHERE libbook.BookId=:id");
$stmt->bindValue(":id", $id, PDO::PARAM_INT);
$stmt->execute();
$book = $stmt->fetch();

if (!$book) {
	http_response_code(404);
	echo "Book not found";
	die();
}

$stmt = $dbh->prepare("SELECT filename FROM book_zip WHERE :id BETWEEN start_id AND end_id AND usr=1 LIMIT 1");
$stmt->bindValue(":id", $id, PDO::PARAM_INT);
$stmt->execute();
$zip_row = $stmt->fetch();

if (!$zip_row) {
	http_response_code(404);
	echo "Archive not found for this book";
	die();
}

$zip_name = $zip_row->filename;
$zip = new ZipArchive();

if ($zip->open(BOOKS_PATH . $zip_name)) {
	$book_title = isset($book->booktitle) ? $book->booktitle : (isset($book->BookTitle) ? $book->BookTitle : 'Book');
	$ext = trim($book->filetype ?? '');
	$filename = ($book->author_name ? $book->author_name . " - " : "") . $book_title . " " . $id . "." . ($book->filename ?? $id) . ($ext ? "." . $ext : "");
	header('Content-Description: File Transfer');
	header('Content-Type: application/octet-stream');
	header('Content-Disposition: attachment; filename=' . basename(rawurlencode($filename)));
	header('Content-Transfer-Encoding: binary');
	header('Expires: 0');
	header('Cache-Control: must-revalidate');
	header('Pragma: public');

	if (!empty($book->filename)) {
		echo $zip->getFromName($book->filename);
	} else {
		echo $zip->getFromName("$id." . $ext);
	}
	$zip->close();
} else {
	echo "NO ZIP";
}



