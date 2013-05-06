<?php

// na�ten� dokumentu XML
$xml = new DOMDocument;
$xml->load("testing-snippet.xml");

// na�ten� stylu XSLT
$xsl = new DOMDocument;
$xsl->load("sablona-xsl2.xml");


// vytvo�en� procesoru XSLT
$proc = new XSLTProcessor;
$proc->importStyleSheet($xsl);

// proveden� transformace
$result = $proc->transformToXML($xml);


//vypsani vysledku do souboru
file_put_contents("testing-processed.xml", $result); 

if (strlen($result)>0){
 echo "transformace probehla";
}

?>
