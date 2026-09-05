<?php
if (function_exists('opds_header')) {
	opds_header($webroot);
} else {
	header('Content-Type: application/atom+xml; charset=utf-8');
	echo '<?xml version="1.0" encoding="utf-8"?>' . "\n";
}
echo <<< _XML
<feed xmlns="http://www.w3.org/2005/Atom" xmlns:dc="http://purl.org/dc/terms/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:os="http://a9.com/-/spec/opensearch/1.1/" xmlns:opds="http://opds-spec.org/2010/catalog">
 <id>tag:root:genres</id>
 <title>Категории жанров</title>
 <updated>$cdt</updated>
 <icon>/favicon.ico</icon>
 <link href="$webroot/opds-opensearch.xml.php" rel="search" type="application/opensearchdescription+xml" />
 <link href="$webroot/opds/search?q={searchTerms}" rel="search" type="application/atom+xml" />
 <link href="$webroot/opds/" rel="start" type="application/atom+xml;profile=opds-catalog" />
 <link href="$webroot/opds/genres" rel="self" type="application/atom+xml;profile=opds-catalog" />
_XML;
$gs = $dbh->prepare("SELECT DISTINCT(genremeta) genre FROM libgenrelist ORDER BY genre");
$gs->execute();

while ($g = $gs->fetch()) {
	$genre_name = $g->genre ?? '';
	echo "<entry> <updated>$cdt</updated>";
	echo " <id>tag:genre:" . urlencode($genre_name) . "</id>";
	echo " <title>" . htmlspecialchars($genre_name, ENT_XML1, 'UTF-8') . "</title>";
	echo " <content type='text'>Подкатегории жанра " . htmlspecialchars($genre_name, ENT_XML1, 'UTF-8') . "</content>";
	echo " <link href='$webroot/opds/listgenres/?id=" . urlencode($genre_name) . "' type='application/atom+xml;profile=opds-catalog' />";
	echo "</entry>\n";
}
echo "</feed>";
?>

