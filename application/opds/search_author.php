<?php
if (function_exists('opds_header')) {
	opds_header($webroot);
} else {
	header('Content-Type: application/atom+xml; charset=utf-8');
	echo '<?xml version="1.0" encoding="utf-8"?>' . "\n";
}

$q = trim($_GET['q'] ?? $_GET['searchTerm'] ?? $_GET['searchTerms'] ?? $_GET['letters'] ?? '');
$page = max(1, intval($_GET['page'] ?? $_GET['pageNumber'] ?? 1));
$limit = 50;
$offset = intval(($page - 1) * $limit);
$fetch_limit = $limit + 1;

$safe_q = htmlspecialchars($q, ENT_XML1, 'UTF-8');
$encoded_q = urlencode($q);

echo <<< _XML
<feed xmlns="http://www.w3.org/2005/Atom" xmlns:dc="http://purl.org/dc/terms/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:os="http://a9.com/-/spec/opensearch/1.1/" xmlns:opds="http://opds-spec.org/2010/catalog">
 <id>tag:root:search:authors</id>
 <title>Поиск авторов: $safe_q</title>
 <updated>$cdt</updated>
 <icon>/favicon.ico</icon>
 <link href="$webroot/opds-opensearch.xml.php" rel="search" type="application/opensearchdescription+xml" />
 <link href="$webroot/opds/search?by=author&amp;q={searchTerms}" rel="search" type="application/atom+xml" />
 <link href="$webroot/opds/" rel="start" type="application/atom+xml;profile=opds-catalog" />
 <link href="$webroot/opds/search?by=author&amp;q=$encoded_q&amp;page=$page" rel="self" type="application/atom+xml;profile=opds-catalog" />

_XML;

if ($q !== '') {
	$param = '%' . $q . '%';
	$authors = $dbh->prepare("SELECT a.AvtorId, a.LastName, a.FirstName, a.MiddleName, a.NickName,
		(SELECT COUNT(*) FROM libavtor la JOIN libbook lb USING(BookId) WHERE lb.deleted='0' AND la.AvtorId = a.AvtorId) as cnt
		FROM libavtorname a
		WHERE a.lastname ILIKE :q OR a.firstname ILIKE :q OR a.nickname ILIKE :q
		ORDER BY a.lastname, a.firstname
		LIMIT $fetch_limit OFFSET $offset");
	$authors->bindValue(":q", $param, PDO::PARAM_STR);
	$authors->execute();

	$rows = $authors->fetchAll();
	$has_next = (count($rows) > $limit);
	if ($has_next) {
		array_pop($rows);
	}

	if ($page > 1) {
		$prev_page = $page - 1;
		echo " <link rel=\"previous\" href=\"$webroot/opds/search?by=author&amp;q=$encoded_q&amp;page=$prev_page\" type=\"application/atom+xml;profile=opds-catalog\" title=\"Предыдущая страница\" />\n";
	}
	if ($has_next) {
		$next_page = $page + 1;
		echo " <link rel=\"next\" href=\"$webroot/opds/search?by=author&amp;q=$encoded_q&amp;page=$next_page\" type=\"application/atom+xml;profile=opds-catalog\" title=\"Следующая страница\" />\n";
	}

	foreach ($rows as $a) {
		$aid = $a->avtorid ?? $a->AvtorId;
		$name = trim(($a->lastname ?? '') . ' ' . ($a->firstname ?? '') . ' ' . ($a->middlename ?? ''));
		if (!empty($a->nickname)) {
			$name .= " (" . $a->nickname . ")";
		}
		if (empty($name)) {
			$name = "Автор #$aid";
		}
		$books_cnt = intval($a->cnt ?? 0);

		echo "<entry>\n";
		echo " <updated>$cdt</updated>\n";
		echo " <id>tag:author:$aid</id>\n";
		echo " <title>" . htmlspecialchars($name, ENT_XML1, 'UTF-8') . "</title>\n";
		echo " <content type='text'>$books_cnt книг</content>\n";
		echo " <link href='" . htmlspecialchars($webroot . "/opds/author?author_id=" . $aid, ENT_QUOTES, 'UTF-8') . "' type='application/atom+xml;profile=opds-catalog' />\n";
		echo "</entry>\n";
	}
}

echo "</feed>";
?>