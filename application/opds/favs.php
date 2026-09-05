<?php
header('Content-Type: application/atom+xml; charset=utf-8');
echo '<?xml version="1.0" encoding="utf-8"?>' . "\n";
echo '<?xml-stylesheet type="text/xsl" href="' . htmlspecialchars($webroot . '/opds.xsl', ENT_QUOTES, 'UTF-8') . '"?>' . "\n";
echo <<< _XML
 <feed xmlns="http://www.w3.org/2005/Atom" xmlns:dc="http://purl.org/dc/terms/" xmlns:os="http://a9.com/-/spec/opensearch/1.1/" xmlns:opds="https://specs.opds.io/opds-1.2">
 <id>tag:root:favs</id>
 <title>Книжные полки</title>
 <updated>$cdt</updated>
 <icon>/favicon.ico</icon>
 <link href="$webroot/opds-opensearch.xml.php" rel="search" type="application/opensearchdescription+xml" />
 <link href="$webroot/opds/search?q={searchTerms}" rel="search" type="application/atom+xml" />
 <link href="$webroot/opds/" rel="start" type="application/atom+xml;profile=opds-catalog" />
 <link href="$webroot/opds/favs/" rel="self" type="application/atom+xml;profile=opds-catalog" />
_XML;

$favs = $dbh->prepare("SELECT * FROM fav_users ORDER BY name");
$favs->execute();

while ($fav = $favs->fetch()) {
	$name = htmlspecialchars($fav->name ?? 'Полка', ENT_XML1, 'UTF-8');
	$uuid = urlencode($fav->user_uuid ?? '');
	echo "<entry>\n <updated>$cdt</updated>";
	echo " <id>tag:fav:$uuid</id>";
	echo " <title>$name</title>";
	echo " <content type='text'>Полка пользователя $name</content>";
	echo " <link href='$webroot/opds/fav/?uuid=$uuid' type='application/atom+xml;profile=opds-catalog' />";
	echo "</entry>\n";
}

echo '</feed>';
?>
