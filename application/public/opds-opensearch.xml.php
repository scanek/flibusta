<?php
include("../init.php");
if (ob_get_length()) ob_clean();
header('Content-Type: application/opensearchdescription+xml; charset=utf-8');
echo '<?xml version="1.0" encoding="utf-8"?>' . "\n";
echo <<< __HTML
<OpenSearchDescription xmlns="http://a9.com/-/spec/opensearch/1.1/">
<ShortName>Библиотека</ShortName>
<Description>Библиотека</Description>
<Tags/>
<Contact/>
<Url type="application/atom+xml" indexOffset="0" pageOffset="0" template="$webroot/opds/search?searchTerm={searchTerms}&amp;searchType=books&amp;pageNumber={startPage?}"/>
<SearchForm>$webroot/opds/search</SearchForm>
<LongName>Библиотека</LongName>
<Image>/favicon.ico</Image>
<Developer/>
<Attribution/>
<SyndicationRight>open</SyndicationRight>
<AdultContent>false</AdultContent>
<Language>*</Language>
<OutputEncoding>UTF-8</OutputEncoding>
<InputEncoding>UTF-8</InputEncoding>
</OpenSearchDescription>
__HTML;
?>