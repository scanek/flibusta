<?php
header('Content-Type: application/atom+xml; charset=utf-8');

$letters = preg_replace('/[^A-Za-zА-Яа-яЁё0-9]/u', '', $_GET['letters'] ?? '');
$length_letters = mb_strlen($letters, 'UTF-8');

echo '<?xml version="1.0" encoding="utf-8"?>' . "\n";
echo '<?xml-stylesheet type="text/xsl" href="' . htmlspecialchars($webroot . '/opds.xsl', ENT_QUOTES, 'UTF-8') . '"?>' . "\n";
echo <<< _XML
 <feed xmlns="http://www.w3.org/2005/Atom" xmlns:dc="http://purl.org/dc/terms/" xmlns:os="http://a9.com/-/spec/opensearch/1.1/" xmlns:opds="https://specs.opds.io/opds-1.2">
 <id>tag:root:sequences</id>
 <title>Книги по сериям</title>
 <updated>$cdt</updated>
 <icon>/favicon.ico</icon>
 <link href="$webroot/opds-opensearch.xml.php" rel="search" type="application/opensearchdescription+xml" />
 <link href="$webroot/opds/search?q={searchTerms}" rel="search" type="application/atom+xml" />
 <link href="$webroot/opds/" rel="start" type="application/atom+xml;profile=opds-catalog" />\n
_XML;

$query="
	SELECT UPPER(SUBSTR(SeqName, 1, ".($length_letters + 1).")) as alpha, COUNT(*) as cnt
	FROM libseqname
	WHERE UPPER(SUBSTR(SeqName, 1, ".($length_letters + 1).")) SIMILAR TO :pattern
	GROUP BY UPPER(SUBSTR(SeqName, 1, ".($length_letters + 1)."))
	ORDER BY alpha";
$ai = $dbh->prepare($query);
$bindparam1 = $letters."_";
$ai->bindParam(":pattern",$bindparam1);
$ai->execute();
while ($ach = $ai->fetchObject()) {
    if ($ach->cnt>30) {
	    echo "\n<entry>\n <updated>$cdt</updated>";
	    echo "<id>tag:sequences:".urlencode($ach->alpha)."</id>\n";
	    echo "<title>".htmlspecialchars($ach->alpha)."</title>\n";
	    echo "<content type='text'>$ach->cnt книжных серий на ".htmlspecialchars($ach->alpha)."</content>\n";
        echo "<link href='$webroot/opds/sequencesindex?letters=".urlencode($ach->alpha)."' type='application/atom+xml;profile=opds-catalog' />\n";
		echo "</entry>";
	} else {
        // list individual serie
        $sq = $dbh->prepare("SELECT SeqName, SeqId 
                from libseqname 
                where UPPER(SUBSTR(SeqName, 1, ".($length_letters + 1).")) = :pattern
                ORDER BY UPPER(SeqName)");
        $sq->bindParam(":pattern",$ach->alpha);
        $sq->execute();
        while($s = $sq->fetchObject()){
            echo "\n<entry>\n <updated>$cdt</updated>";
	        echo "<id>tag:sequence:$s->seqid</id>\n";
            echo "<title>". htmlspecialchars($s->seqname)."</title>\n";
            echo " <content type='text'></content>";
	        echo " <link href='$webroot/opds/list?seq_id=" . $s->seqid . "' type='application/atom+xml;profile=opds-catalog' />";
	        echo "</entry>";
        }
        $sq = null;
	}
}
echo '</feed>';
?>