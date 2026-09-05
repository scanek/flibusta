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

$stmt = $dbh->prepare("SELECT libbook.Title BookTitle, 
	CONCAT(libavtorname.LastName, ' ', libavtorname.FirstName) author_name
		FROM libbook 
		LEFT JOIN libbannotations USING(BookId) 
		LEFT JOIN libgenre USING(BookId) 
		LEFT JOIN libgenrelist USING(GenreId) 
		LEFT JOIN libseq USING(BookId) 
		LEFT JOIN libavtor USING(BookId) 
		LEFT JOIN libavtorname USING(AvtorId) 
		LEFT JOIN libseqname USING(SeqId) WHERE libbook.BookId=:id");
$stmt->bindValue(":id", $id, PDO::PARAM_INT);
$stmt->execute();
$book = $stmt->fetch();

if (!$book) {
	http_response_code(404);
	echo "Book not found";
	die();
}

$stmt = $dbh->prepare("SELECT filename FROM book_zip WHERE :id BETWEEN start_id AND end_id AND usr=0 LIMIT 1");
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

if ($zip->open(ROOT_PATH . "flibusta/" . $zip_name)) {
	$book_title = isset($book->booktitle) ? $book->booktitle : (isset($book->BookTitle) ? $book->BookTitle : 'Book');
	$filename = ($book->author_name ? $book->author_name . " - " : "") . $book_title . " " . $id . ".fb2";
	header('Content-Description: File Transfer');
	header('Content-Type: application/octet-stream');
	header('Content-Disposition: attachment; filename=' . basename(rawurlencode($filename)));
	header('Content-Transfer-Encoding: binary');
	header('Expires: 0');
	header('Cache-Control: must-revalidate');
	header('Pragma: public');
	echo $zip->getFromName("$id.fb2");
	$zip->close();
} else {
	echo "NO ZIP";
}



