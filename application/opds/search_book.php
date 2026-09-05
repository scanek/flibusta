<?php
if (function_exists('opds_header')) {
	opds_header($webroot);
} else {
	header('Content-Type: application/atom+xml; charset=utf-8');
	echo '<?xml version="1.0" encoding="utf-8"?>' . "\n";
}

$q = trim($_GET['q'] ?? $_GET['searchTerm'] ?? $_GET['searchTerms'] ?? '');
$page = max(1, intval($_GET['page'] ?? $_GET['pageNumber'] ?? 1));
$limit = intval(OPDS_FEED_COUNT);
$offset = intval(($page - 1) * $limit);
$fetch_limit = $limit + 1;

$safe_q = htmlspecialchars($q, ENT_XML1, 'UTF-8');
$encoded_q = urlencode($q);

echo <<< _XML
<feed xmlns="http://www.w3.org/2005/Atom" xmlns:dc="http://purl.org/dc/terms/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:os="http://a9.com/-/spec/opensearch/1.1/" xmlns:opds="http://opds-spec.org/2010/catalog">
 <id>tag:root:search:books</id>
 <title>Результаты поиска: $safe_q</title>
 <updated>$cdt</updated>
 <icon>/favicon.ico</icon>
 <link href="$webroot/opds-opensearch.xml.php" rel="search" type="application/opensearchdescription+xml" />
 <link href="$webroot/opds/search?q={searchTerms}" rel="search" type="application/atom+xml" />
 <link href="$webroot/opds/" rel="start" type="application/atom+xml;profile=opds-catalog" />
 <link href="$webroot/opds/search?q=$encoded_q&amp;page=$page" rel="self" type="application/atom+xml;profile=opds-catalog" />

_XML;

if ($q !== '') {
	$cnt_param = '%' . $q . '%';
	$books = $dbh->prepare("SELECT b.*
		FROM libbook b
		WHERE b.deleted='0' AND b.Title ILIKE :q
		ORDER BY b.Time DESC
		LIMIT $fetch_limit OFFSET $offset");
	$books->bindValue(":q", $cnt_param, PDO::PARAM_STR);
	$books->execute();

	$rows = $books->fetchAll();
	$has_next = (count($rows) > $limit);
	if ($has_next) {
		array_pop($rows);
	}

	if ($page > 1) {
		$prev_page = $page - 1;
		echo " <link rel=\"previous\" href=\"$webroot/opds/search?q=$encoded_q&amp;page=$prev_page\" type=\"application/atom+xml;profile=opds-catalog\" title=\"Предыдущая страница\" />\n";
	}
	if ($has_next) {
		$next_page = $page + 1;
		echo " <link rel=\"next\" href=\"$webroot/opds/search?q=$encoded_q&amp;page=$next_page\" type=\"application/atom+xml;profile=opds-catalog\" title=\"Следующая страница\" />\n";
	}

	foreach ($rows as $b) {
		opds_book($b, $webroot);
	}
}

echo "</feed>";
?>