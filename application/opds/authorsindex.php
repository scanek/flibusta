<?php
$letters = preg_replace('/[^A-Za-zА-Яа-яЁё0-9]/u', '', $_GET['letters'] ?? '');
$length_letters = mb_strlen($letters, 'UTF-8');

if (function_exists('opds_header')) {
	opds_header($webroot);
} else {
	header('Content-Type: application/atom+xml; charset=utf-8');
	echo '<?xml version="1.0" encoding="utf-8"?>' . "\n";
}
echo <<< _XML
 <feed xmlns="http://www.w3.org/2005/Atom" xmlns:dc="http://purl.org/dc/terms/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:os="http://a9.com/-/spec/opensearch/1.1/" xmlns:opds="http://opds-spec.org/2010/catalog">
 <id>tag:root:authors</id>
 <title>Книги по авторам</title>
 <updated>$cdt</updated>
 <icon>/favicon.ico</icon>
 <link href="$webroot/opds-opensearch.xml.php" rel="search" type="application/opensearchdescription+xml" />
 <link href="$webroot/opds/search?by=author&amp;q={searchTerms}" rel="search" type="application/atom+xml" />
 <link href="$webroot/opds/" rel="start" type="application/atom+xml;profile=opds-catalog" />\n
_XML;

$target_len = $length_letters + 1;
$pattern = $letters . '[A-ZА-ЯЁ]';
$query = "
	SELECT UPPER(SUBSTR(LastName, 1, $target_len)) as alpha, COUNT(*) as cnt
	FROM libavtorname
	WHERE UPPER(SUBSTR(LastName, 1, $target_len)) SIMILAR TO :pattern
	GROUP BY UPPER(SUBSTR(LastName, 1, $target_len))
	ORDER BY alpha";
$ai = $dbh->prepare($query);
$ai->bindValue(":pattern", $pattern);
$ai->execute();

while ($ach = $ai->fetch()) {
	$alpha = $ach->alpha;
	$safe_alpha = htmlspecialchars($alpha, ENT_XML1, 'UTF-8');
	$cnt = intval($ach->cnt);
	echo "\n<entry>\n <updated>$cdt</updated>\n";
	echo " <id>tag:authors:" . urlencode($alpha) . "</id>\n";
	echo " <title>$safe_alpha</title>\n";
	echo " <content type='text'>$cnt авторов на $safe_alpha</content>\n";
	if ($cnt > 500) {
		$url = "$webroot/opds/authorsindex?letters=" . urlencode($alpha);
	} else {
		$url = "$webroot/opds/search?by=author&amp;q=" . urlencode($alpha);
	}
	echo " <link href='$url' type='application/atom+xml;profile=opds-catalog' />\n";
	echo "</entry>\n";
}
echo '</feed>';
?>