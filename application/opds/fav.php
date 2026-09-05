<?php
$uuid = $_GET['uuid'] ?? '';
$safe_uuid = htmlspecialchars($uuid, ENT_QUOTES, 'UTF-8');

$shelf_name = 'Избранное';
if (!empty($uuid)) {
	$u_stmt = $dbh->prepare("SELECT name FROM fav_users WHERE user_uuid=:uuid LIMIT 1");
	$u_stmt->bindValue(":uuid", $uuid);
	$u_stmt->execute();
	if ($u = $u_stmt->fetch()) {
		$shelf_name = $u->name ?? 'Избранное';
	}
}
$safe_title = htmlspecialchars("Полка: $shelf_name", ENT_XML1, 'UTF-8');

opds_header($webroot);
echo <<< _XML
<feed xmlns="http://www.w3.org/2005/Atom" xmlns:dc="http://purl.org/dc/terms/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:os="http://a9.com/-/spec/opensearch/1.1/" xmlns:opds="http://opds-spec.org/2010/catalog">
<id>tag:root:fav:$safe_uuid</id>
<title>$safe_title</title>
<updated>$cdt</updated>
<icon>/favicon.ico</icon>
<link href="$webroot/opds-opensearch.xml.php" rel="search" type="application/opensearchdescription+xml" />
<link href="$webroot/opds/search?q={searchTerms}" rel="search" type="application/atom+xml" />
<link href="$webroot/opds/" rel="start" type="application/atom+xml;profile=opds-catalog" />
<link href="$webroot/opds/fav/?uuid=$safe_uuid" rel="self" type="application/atom+xml;profile=opds-catalog" />

_XML;

$books = $dbh->prepare("SELECT DISTINCT b.*
		FROM fav f
		LEFT JOIN libbook b USING(bookid)
		WHERE user_uuid=:uuid AND f.bookid IS NOT NULL AND b.deleted='0'
		ORDER BY b.time DESC");
$books->bindValue(":uuid", $uuid);
$books->execute();

while ($b = $books->fetch()) {
	opds_book($b, $webroot);
}

echo "</feed>";
?>
