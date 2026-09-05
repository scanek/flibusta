<?php
header('Content-Type: application/atom+xml; charset=utf-8');
$meta_id = $_GET['id'] ?? '';
$safe_meta = htmlspecialchars($meta_id, ENT_XML1, 'UTF-8');
echo '<?xml version="1.0" encoding="utf-8"?>' . "\n";
echo '<?xml-stylesheet type="text/xsl" href="' . htmlspecialchars($webroot . '/opds.xsl', ENT_QUOTES, 'UTF-8') . '"?>' . "\n";
echo '<feed xmlns="http://www.w3.org/2005/Atom" xmlns:dc="http://purl.org/dc/terms/" xmlns:os="http://a9.com/-/spec/opensearch/1.1/" xmlns:opds="https://specs.opds.io/opds-1.2">' . "\n";
echo "<id>tag:root:genres:" . urlencode($meta_id) . "</id>\n";
echo "<title>Жанры: $safe_meta</title>\n";
echo "<updated>$cdt</updated>\n";
echo <<< _XML
 <icon>/favicon.ico</icon>
 <link href="$webroot/opds-opensearch.xml.php" rel="search" type="application/opensearchdescription+xml" />
 <link href="$webroot/opds/search?q={searchTerms}" rel="search" type="application/atom+xml" />
 <link href="$webroot/opds/" rel="start" type="application/atom+xml;profile=opds-catalog" />
 <link href="$webroot/opds/listgenres/?id=" . urlencode($meta_id) . "" rel="self" type="application/atom+xml;profile=opds-catalog" />

_XML;

$gs = $dbh->prepare("SELECT g.*,
	(SELECT COUNT(*) FROM libgenre lg JOIN libbook lb USING(BookId) WHERE lb.deleted='0' AND lg.genreid=g.genreid) cnt
	FROM libgenrelist g
	WHERE g.genremeta=:id
	ORDER BY g.genredesc");
$gs->bindValue(":id", $meta_id);
$gs->execute();

while ($g = $gs->fetch()) {
	$gid = $g->genreid ?? $g->GenreId;
	$gcode = $g->genrecode ?? $g->GenreCode ?? '';
	$gdesc = $g->genredesc ?? $g->GenreDesc ?? '';
	$cnt = intval($g->cnt ?? 0);
	echo "<entry>\n";
	echo " <updated>$cdt</updated>\n";
	echo " <id>tag:genre:$gcode</id>\n";
	echo " <title>" . htmlspecialchars($gdesc, ENT_XML1, 'UTF-8') . "</title>\n";
	echo " <content type='text'>Книг: $cnt</content>\n";
	echo " <link href='$webroot/opds/list/?genre_id=$gid' type='application/atom+xml;profile=opds-catalog' />\n";
	echo "</entry>\n";
}
echo '</feed>';
?>

