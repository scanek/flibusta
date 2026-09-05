<?php
header('Content-Type: application/atom+xml; charset=utf-8');
echo '<?xml version="1.0" encoding="utf-8"?>' . "\n";
echo '<?xml-stylesheet type="text/xsl" href="' . htmlspecialchars($webroot . '/opds.xsl', ENT_QUOTES, 'UTF-8') . '"?>' . "\n";

$q = trim($_GET['q'] ?? $_GET['searchTerm'] ?? $_GET['searchTerms'] ?? '');
$page = max(1, intval($_GET['page'] ?? $_GET['pageNumber'] ?? 1));
$limit = OPDS_FEED_COUNT;
$offset = ($page - 1) * $limit;

$safe_q = htmlspecialchars($q, ENT_XML1, 'UTF-8');
$encoded_q = urlencode($q);

echo <<< _XML
<feed xmlns="http://www.w3.org/2005/Atom" xmlns:dc="http://purl.org/dc/terms/" xmlns:os="http://a9.com/-/spec/opensearch/1.1/" xmlns:opds="https://specs.opds.io/opds-1.2">
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
	$cnt_stmt = $dbh->prepare("SELECT COUNT(*) as cnt FROM libbook WHERE deleted='0' AND Title ILIKE :q");
	$cnt_param = '%' . $q . '%';
	$cnt_stmt->bindValue(":q", $cnt_param, PDO::PARAM_STR);
	$cnt_stmt->execute();
	$total = intval($cnt_stmt->fetch()->cnt ?? 0);

	echo " <os:totalResults>$total</os:totalResults>\n";
	echo " <os:startIndex>$offset</os:startIndex>\n";
	echo " <os:itemsPerPage>$limit</os:itemsPerPage>\n";

	if ($page > 1) {
		$prev_page = $page - 1;
		echo " <link rel=\"previous\" href=\"$webroot/opds/search?q=$encoded_q&amp;page=$prev_page\" type=\"application/atom+xml;profile=opds-catalog\" title=\"Предыдущая страница\" />\n";
	}
	if ($offset + $limit < $total) {
		$next_page = $page + 1;
		echo " <link rel=\"next\" href=\"$webroot/opds/search?q=$encoded_q&amp;page=$next_page\" type=\"application/atom+xml;profile=opds-catalog\" title=\"Следующая страница\" />\n";
	}

	$books = $dbh->prepare("SELECT b.*
		FROM libbook b
		WHERE b.deleted='0' AND b.Title ILIKE :q
		ORDER BY b.Time DESC
		LIMIT :limit OFFSET :offset");
	$books->bindValue(":q", $cnt_param, PDO::PARAM_STR);
	$books->bindValue(":limit", $limit, PDO::PARAM_INT);
	$books->bindValue(":offset", $offset, PDO::PARAM_INT);
	$books->execute();

	while ($b = $books->fetch()) {
		opds_book($b, $webroot);
	}
}

echo "</feed>";
?>