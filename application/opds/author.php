<?php
$author_id = intval($_GET['author_id'] ?? 0);
$seq_mode = isset($_GET['seq']);

if ($author_id <= 0) {
	opds_header($webroot);
	echo <<< _XML
<feed xmlns="http://www.w3.org/2005/Atom" xmlns:dc="http://purl.org/dc/terms/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:os="http://a9.com/-/spec/opensearch/1.1/" xmlns:opds="http://opds-spec.org/2010/catalog">
 <id>tag:author:0</id>
 <title>Автор не указан</title>
 <updated>$cdt</updated>
 <icon>/favicon.ico</icon>
 <link href="$webroot/opds/" rel="start" type="application/atom+xml;profile=opds-catalog" />
</feed>
_XML;
	exit;
}

if (!$seq_mode) {  
	$stmt = $dbh->prepare("SELECT a.LastName as LastName, a.MiddleName as MiddleName, a.FirstName as FirstName, a.NickName as NickName,
		aa.Body as Body, p.File as picFile 
		FROM libavtorname a 
		LEFT JOIN libaannotations aa ON a.avtorid = aa.avtorid
		LEFT JOIN libapics p ON a.avtorid = p.avtorid
		WHERE a.avtorid = :authorid");
} else {
	$stmt = $dbh->prepare("SELECT LastName, MiddleName, FirstName, NickName FROM libavtorname WHERE avtorid = :authorid");
}

$stmt->bindValue(':authorid', $author_id, PDO::PARAM_INT);
$stmt->execute();
$a = $stmt->fetchObject();

if (!$a) {
	opds_header($webroot);
	echo <<< _XML
<feed xmlns="http://www.w3.org/2005/Atom" xmlns:dc="http://purl.org/dc/terms/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:os="http://a9.com/-/spec/opensearch/1.1/" xmlns:opds="http://opds-spec.org/2010/catalog">
 <id>tag:author:$author_id</id>
 <title>Автор #$author_id не найден</title>
 <updated>$cdt</updated>
 <icon>/favicon.ico</icon>
 <link href="$webroot/opds/" rel="start" type="application/atom+xml;profile=opds-catalog" />
</feed>
_XML;
	exit;
}

$firstname = $a->firstname ?? $a->FirstName ?? '';
$middlename = $a->middlename ?? $a->MiddleName ?? '';
$lastname = $a->lastname ?? $a->LastName ?? '';
$nickname = $a->nickname ?? $a->NickName ?? '';
$raw_name = trim("$firstname $middlename $lastname");
if ($nickname !== '') {
	$raw_name .= " ($nickname)";
}
if ($raw_name === '') {
	$raw_name = "Автор #$author_id";
}
$author_name = htmlspecialchars($raw_name, ENT_XML1, 'UTF-8');

opds_header($webroot);

if ($seq_mode) {
	echo <<< _XML
<feed xmlns="http://www.w3.org/2005/Atom" xmlns:dc="http://purl.org/dc/terms/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:os="http://a9.com/-/spec/opensearch/1.1/" xmlns:opds="http://opds-spec.org/2010/catalog">
 <id>tag:author:$author_id:sequences</id>
 <title>$author_name : Книги по сериям</title>
 <updated>$cdt</updated>
 <icon>/favicon.ico</icon>
 <link href="$webroot/opds-opensearch.xml.php" rel="search" type="application/opensearchdescription+xml" />
 <link href="$webroot/opds/search?by=author&amp;q={searchTerms}" rel="search" type="application/atom+xml" />
 <link href="$webroot/opds/" rel="start" type="application/atom+xml;profile=opds-catalog" />
 <link href="$webroot/opds/author?author_id=$author_id&amp;seq=1" rel="self" type="application/atom+xml;profile=opds-catalog" />

_XML;

	$sequences = $dbh->prepare("SELECT DISTINCT sn.seqid, sn.seqname
		FROM libseqname sn
		JOIN libseq s ON sn.seqid = s.seqid
		JOIN libavtor a ON s.bookid = a.bookid
		WHERE a.avtorid = :aid
		ORDER BY sn.seqname");
	$sequences->bindValue(":aid", $author_id, PDO::PARAM_INT);
	$sequences->execute();
	while ($seq = $sequences->fetchObject()) {
		$sname = htmlspecialchars($seq->seqname ?? '', ENT_XML1, 'UTF-8');
		$sid = intval($seq->seqid ?? 0);
		echo "<entry>\n";
		echo " <updated>$cdt</updated>\n";
		echo " <id>tag:sequence:$sid</id>\n";
		echo " <title>$sname</title>\n";
		echo " <link href='$webroot/opds/list?seq_id=$sid' type='application/atom+xml;profile=opds-catalog' />\n";
		echo "</entry>\n";
	}
} else {
	echo <<< _XML
<feed xmlns="http://www.w3.org/2005/Atom" xmlns:dc="http://purl.org/dc/terms/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:os="http://a9.com/-/spec/opensearch/1.1/" xmlns:opds="http://opds-spec.org/2010/catalog">
 <id>tag:author:$author_id</id>
 <title>$author_name</title>
 <updated>$cdt</updated>
 <icon>/favicon.ico</icon>
 <link href="$webroot/opds-opensearch.xml.php" rel="search" type="application/opensearchdescription+xml" />
 <link href="$webroot/opds/search?by=author&amp;q={searchTerms}" rel="search" type="application/atom+xml" />
 <link href="$webroot/opds/" rel="start" type="application/atom+xml;profile=opds-catalog" />
 <link href="$webroot/opds/author?author_id=$author_id" rel="self" type="application/atom+xml;profile=opds-catalog" />

_XML;

	$body = $a->body ?? $a->Body ?? '';
	$picfile = $a->picfile ?? $a->picFile ?? null;
	if (!empty($body)) {
		echo "<entry>\n";
		echo " <updated>$cdt</updated>\n";
		echo " <id>tag:author:bio:$author_id</id>\n";
		echo " <title>Об авторе</title>\n";
		if (!empty($picfile)) {
			echo " <link href=\"$webroot/extract_author.php?id=$author_id\" rel=\"http://opds-spec.org/image\" type=\"image/jpeg\" />\n";
			echo " <link href=\"$webroot/extract_author.php?id=$author_id\" rel=\"http://opds-spec.org/image/thumbnail\" type=\"image/jpeg\" />\n";
		}
		echo " <content type=\"text/html\"><![CDATA[" . $body . "]]></content>\n";
		echo " <link href=\"$webroot/author/view/$author_id\" rel=\"alternate\" type=\"text/html\" title=\"Страница автора на сайте\" />\n";
		echo "</entry>\n";
	}

	echo <<< _XML
<entry>
 <updated>$cdt</updated>
 <title>Все книги автора (по новизне)</title>
 <id>tag:author:$author_id:list</id>
 <link href="$webroot/opds/list?author_id=$author_id" type="application/atom+xml;profile=opds-catalog" />
</entry>
<entry>
 <updated>$cdt</updated>
 <title>Книги автора по алфавиту</title>
 <id>tag:author:$author_id:alphabet</id>
 <link href="$webroot/opds/list?author_id=$author_id&amp;display_type=alphabet" type="application/atom+xml;profile=opds-catalog" />
</entry>
<entry>
 <updated>$cdt</updated>
 <title>Книги автора по году издания</title>
 <id>tag:author:$author_id:year</id>
 <link href="$webroot/opds/list?author_id=$author_id&amp;display_type=year" type="application/atom+xml;profile=opds-catalog" />
</entry>
<entry>
 <updated>$cdt</updated>
 <title>Книжные серии с произведениями автора</title>
 <id>tag:author:$author_id:sequences</id>
 <link href="$webroot/opds/author?author_id=$author_id&amp;seq=1" type="application/atom+xml;profile=opds-catalog" />
</entry>
<entry>
 <updated>$cdt</updated>
 <title>Произведения вне серий</title>
 <id>tag:author:$author_id:sequenceless</id>
 <link href="$webroot/opds/list?author_id=$author_id&amp;display_type=sequenceless" type="application/atom+xml;profile=opds-catalog" />
</entry>
_XML;
}

echo "\n</feed>";
$stmt = null;
?>
