<?php

// naètení dokumentu XML
$xml = new DOMDocument;
$xml->load("testing-snippet.xml");

// naètení stylu XSLT
$xsl = new DOMDocument;
$xsl->load("sablona-xsl2.xml");


// vytvoøení procesoru XSLT
$proc = new XSLTProcessor;
$proc->importStyleSheet($xsl);

// provedení transformace
$result = $proc->transformToXML($xml);


//vypsani vysledku do souboru
file_put_contents("testing-processed.xml", $result); 

if (strlen($result)>0){
 echo "transformace probehla";
}

?>
