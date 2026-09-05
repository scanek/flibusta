<?php
ini_set('display_errors', '0');
if (ob_get_length()) {
	ob_clean();
}

$is_opds_browser = !empty($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'text/html');

function opds_header($webroot) {
	global $is_opds_browser;
	header('Content-Type: application/atom+xml; charset=utf-8');
	echo '<?xml version="1.0" encoding="utf-8"?>' . "\n";
	if ($is_opds_browser) {
		echo '<?xml-stylesheet type="text/xsl" href="' . htmlspecialchars($webroot . '/opds.xsl', ENT_QUOTES, 'UTF-8') . '"?>' . "\n";
	}
}

switch ($url->action) {
	case 'list':
		include('list.php');
		break;
	case 'authorsindex':
		include('authorsindex.php');
		break;
	case 'author':
		include('author.php');
		break;
	case 'sequencesindex':
		include('sequencesindex.php');
		break;
	case 'genres':
		include('genres.php');
		break;
	case 'listgenres':
		include('listgenres.php');
		break;
	case 'fav':
		include('fav.php');
		break;
	case 'favs':
		include('favs.php');
		break;
	case 'search':
		include('search.php');
		break;

	default:
		include('main.php');
}
