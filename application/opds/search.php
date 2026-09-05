<?php
$by = $_GET['by'] ?? $_GET['searchType'] ?? '';
switch ($by) {
	case 'author':
	case 'authors':
		include('search_author.php');
		break;

	case 'books':
	case 'book':
	default:
		include('search_book.php');
}
?>