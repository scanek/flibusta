<?php
header('Content-Type: application/atom+xml; charset=utf-8');
echo '<?xml version="1.0" encoding="utf-8"?>' . "\n";
echo '<?xml-stylesheet type="text/xsl" href="' . htmlspecialchars($webroot . '/opds.xsl', ENT_QUOTES, 'UTF-8') . '"?>' . "\n";

$filter = "b.deleted='0' ";
$join = '';

$orderby = ' b.time DESC ';

$title = 'в новинках';

if (isset($_GET['genre_id'])) {
	$gid = intval($_GET['genre_id']);
	$filter .= 'AND g.genreid=:gid ';
	$join .= 'LEFT JOIN libgenre g USING(BookId) ';
	$orderby = ' b.time DESC ';
	$stmt = $dbh->prepare("SELECT * FROM libgenrelist WHERE genreid=:gid");
	$stmt->bindValue(":gid", $gid, PDO::PARAM_INT);
	$stmt->execute();
	if ($g = $stmt->fetch()) {
		$title = "в $g->genremeta: $g->genredesc";
	}
}

if (isset($_GET['seq_id'])) {
	$sid = intval($_GET['seq_id']);
	$filter .= 'AND s.seqid=:sid ';
	$join .= 'LEFT JOIN libseq s USING(BookId) ';
	$orderby = " s.seqnumb ASC, b.time DESC ";
	$stmt = $dbh->prepare("SELECT * FROM libseqname WHERE seqid=:sid");
	$stmt->bindValue(":sid", $sid, PDO::PARAM_INT);
	$stmt->execute();
	if ($s = $stmt->fetch()) {
		$title = "в серии $s->seqname";
	}
}

if (isset($_GET['author_id'])) {
	$aid = intval($_GET['author_id']);
	$filter .= 'AND a.avtorid=:aid ';
	$join .= 'JOIN libavtor a USING (bookid) JOIN libavtorname an USING (avtorid) ';
	
	$display_type = (isset($_GET['display_type'])) ? $_GET['display_type'] : '';
	if ($display_type == 'sequenceless') {
		$filter .= 'AND s.seqid is null ';
		$join .= 'LEFT JOIN libseq s ON s.bookId = b.bookId ';
		$orderby = ' b.time DESC ';
	} else if ($display_type == 'year') {
		$orderby = ' b.year DESC, b.time DESC ';
	} else if ($display_type == 'alphabet') {
		$orderby = ' b.title ASC ';
	} else {
		$orderby = ' b.time DESC ';
	}
	$stmt = $dbh->prepare("SELECT * FROM libavtorname WHERE avtorid=:aid");
	$stmt->bindValue(":aid", $aid, PDO::PARAM_INT);
	$stmt->execute();
	if ($a = $stmt->fetch()) {
		$aname = ($a->nickname != '') ? "$a->firstname $a->middlename $a->lastname ($a->nickname)" : "$a->firstname $a->middlename $a->lastname";
		$title = "автора " . trim($aname);
	}
}

$page = max(1, intval($_GET['page'] ?? $_GET['pageNumber'] ?? 1));
$limit = OPDS_FEED_COUNT;
$offset = ($page - 1) * $limit;

$params = $_GET;
unset($params['page'], $params['pageNumber']);
$base_query = http_build_query($params);
$page_prefix = $webroot . '/opds/list?' . ($base_query ? $base_query . '&amp;' : '');

$safe_title = htmlspecialchars("Книги $title", ENT_XML1, 'UTF-8');

echo <<< _XML
<feed xmlns="http://www.w3.org/2005/Atom" xmlns:dc="http://purl.org/dc/terms/" xmlns:os="http://a9.com/-/spec/opensearch/1.1/" xmlns:opds="https://specs.opds.io/opds-1.2">
<id>tag:root:list</id>
<title>$safe_title</title>
<updated>$cdt</updated>
<icon>/favicon.ico</icon>
<link href="$webroot/opds-opensearch.xml.php" rel="search" type="application/opensearchdescription+xml" />
<link href="$webroot/opds/search?q={searchTerms}" rel="search" type="application/atom+xml" />
<link href="$webroot/opds/" rel="start" type="application/atom+xml;profile=opds-catalog" />
<link href="{$page_prefix}page=$page" rel="self" type="application/atom+xml;profile=opds-catalog" />

_XML;

$cnt_stmt = $dbh->prepare("SELECT COUNT(*) as cnt FROM libbook b $join WHERE $filter");
if (isset($_GET['genre_id'])) {
	$cnt_stmt->bindValue(":gid", $gid, PDO::PARAM_INT);
}
if (isset($_GET['seq_id'])) {
	$cnt_stmt->bindValue(":sid", $sid, PDO::PARAM_INT);
}
if (isset($_GET['author_id'])) {
	$cnt_stmt->bindValue(":aid", $aid, PDO::PARAM_INT);
}
$cnt_stmt->execute();
$total = intval($cnt_stmt->fetch()->cnt ?? 0);

echo " <os:totalResults>$total</os:totalResults>\n";
echo " <os:startIndex>$offset</os:startIndex>\n";
echo " <os:itemsPerPage>$limit</os:itemsPerPage>\n";

if ($page > 1) {
	$prev_page = $page - 1;
	echo " <link rel=\"previous\" href=\"{$page_prefix}page=$prev_page\" type=\"application/atom+xml;profile=opds-catalog\" title=\"Предыдущая страница\" />\n";
}
if ($offset + $limit < $total) {
	$next_page = $page + 1;
	echo " <link rel=\"next\" href=\"{$page_prefix}page=$next_page\" type=\"application/atom+xml;profile=opds-catalog\" title=\"Следующая страница\" />\n";
}

$books = $dbh->prepare("SELECT b.*
	FROM libbook b
	$join
	WHERE
	$filter
	ORDER BY $orderby
	LIMIT :limit OFFSET :offset");

if (isset($_GET['genre_id'])) {
	$books->bindValue(":gid", $gid, PDO::PARAM_INT);
}
if (isset($_GET['seq_id'])) {
	$books->bindValue(":sid", $sid, PDO::PARAM_INT);
}
if (isset($_GET['author_id'])) {
	$books->bindValue(":aid", $aid, PDO::PARAM_INT);
}
$books->bindValue(":limit", $limit, PDO::PARAM_INT);
$books->bindValue(":offset", $offset, PDO::PARAM_INT);

$books->execute();

while ($b = $books->fetch()) {
	opds_book($b, $webroot);
}

echo "</feed>";
?>