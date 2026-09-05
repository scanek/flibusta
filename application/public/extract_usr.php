<?php
include('../init.php');

if (isset($_GET['id'])) {
	$id = intval($_GET['id']);
} else {
	$id = 610095;
}
if ($id <= 0) {
	die();
}

$stmt = $dbh->prepare("SELECT libbook.Title BookTitle, libbook.FileType, libfilename.filename,
	CONCAT(libavtorname.LastName, ' ', libavtorname.FirstName) author_name
		FROM libbook 
		LEFT JOIN libavtor USING(BookId) 
		LEFT JOIN libavtorname USING(AvtorId) 
		LEFT JOIN libfilename USING(BookId) 
		WHERE libbook.BookId=:id");
$stmt->bindValue(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$book = $stmt->fetch();

if (!$book) {
	http_response_code(404);
	echo "Book not found";
	die();
}

$stmt = $dbh->prepare("SELECT filename FROM libfilename WHERE BookId=:id LIMIT 1");
$stmt->bindValue(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$fn_row = $stmt->fetch();
$usr_filename = ($fn_row && !empty($fn_row->filename)) ? $fn_row->filename : trim("$id." . ($book->filetype ?? ''));

$stmt = $dbh->prepare("SELECT filename FROM book_zip WHERE (:id BETWEEN start_id AND end_id) AND usr=1 LIMIT 1");
$stmt->bindValue(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$zip_row = $stmt->fetch();

if (!$zip_row) {
	http_response_code(404);
	echo "Archive not found";
	die();
}

$zip_name = $zip_row->filename;
$zip = new ZipArchive(); 

if ($zip->open(BOOKS_PATH . $zip_name)) {
	$book_title = isset($book->booktitle) ? $book->booktitle : (isset($book->BookTitle) ? $book->BookTitle : 'Book');
	$ext = trim($book->filetype ?? '');
	$filename = ($book->author_name ? $book->author_name . " - " : "") . $book_title . " " . ($book->filename ?? $id) . ($ext ? "." . $ext : "");

	header('Content-Description: File Transfer');
	header('Content-Type: application/octet-stream');
	header('Content-Transfer-Encoding: binary');
	header('Expires: 0');
	header('Cache-Control: must-revalidate');
	header('Pragma: public');
	header('Content-Disposition: attachment; filename=' . basename(rawurlencode($filename)));

	$data = $zip->getFromName($usr_filename);
	if ($data === false || $data == '') {
		$data = $zip->getFromName($usr_filename . ".zip");
	}

	echo $data;
	$zip->close();
} else {
	echo "NO ZIP";
}


