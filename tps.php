<?php

//includuju knihovnu ktera pripravi parametry saxonu, nalezne cesty k xml, xsl a definuje output
//uklada si postupne temp soubory ktere jsou po procesu smazany
//knihovnu jsem upravil, tak aby vyhovovala mym podminkam, tudiz nejde pouzit tu samou znovu stazenou
// prikaz __DR__ vyzaduje pouziti php verse 5.3 a vyssi
include (__DIR__. "/xml/XSLT2Processor.php");

//priprava na parametr posledni aktualizace - pravdepodobne si budu ukladat do souboru cas posledni zmeny, a potom si ho nactu
$odkdy = time() - (60*60*24*7);
//$odkdy = file_get_contents("lastakt.txt");

run($odkdy);


//funkce obsluhujici prubeh transformace, pomoci if podminek vypisuje stavy
function run($odkdy){
  $rtoken = "";
  //podminka pro prvotni nacitani cele databaze, pro aktualizace nebude potreba
  if(file_exists("resumptiontoken.txt")){
    $rtoken = file_get_contents("resumptiontoken.txt");
  }
  if (strlen($rtoken)>0){    
    $data = getdata($rtoken, "");
  }
  //do else bude vstupovat prubezna aktualizace, musi byt pouzit parametr 'from' v requestu na nusl
  //po transformaci cele databaze by se mela postarat funkce accept o smazani souboru resumptiontoken.txt
  else {     
    //$data = getdata("");
    $data = getdata("", $odkdy);
  }

  //podminka ktera po nacteni dat zapise do souboru atualni resumption token, nebo pokud neexistuje, tak soubor smaze
  if(!accept($data)){
    echo "token nebyl zapsan do souboru!";    
  }
  //pojmenovani souboru tak, aby kazdy mel jine jmeno, zatim pomoci casove znamky. mozna je treba zmenit kvuli nacitani do virtuosa
  $file = time() . ".xml";
  
  //podminka volajici funkci write, ktera zapise do souboru data transformovana funkci xsltprocess  
  if(!write(xsltprocess($data), $file)){
    echo "neprobehl zapis dat do souboru!";
  }
  else {
    echo "vsechno probehlo v poradku";
  }

}

//pridat funkci druhy parametr a pridat podminku na jeho prazdnost .. plus do query pridat from parametr
function getdata($token, $cas){
  if(strlen($token)>0){                                                                                     
    $base = file_get_contents("http://invenio.nusl.cz/oai2d?verb=ListRecords&resumptionToken=". $token);    
  }
  
  if (strlen($cas)>0) {
    $base = file_get_contents("http://invenio.nusl.cz/oai2d?verb=ListRecords&metadataPrefix=oai_dc&from=". $cas);
  }
  if (strlen($token)==0 && strlen($cas)==0) {   //pouze pri uplne prvnim spusteni                                                                                                    
    $base = file_get_contents("http://invenio.nusl.cz/oai2d?verb=ListRecords&metadataPrefix=oai_dc");                           
  }

  //pokud se povedlo ziskat nejaka data timto requestem..
  if(strlen($base)>0){
   return $base;  
  }
  else {
   echo "nepovedlo se ziskat data!";
   exit;
  }

}

//kontroluje resumption token a zapisuje ho do souboru, kdyz neexistuje, tak se soubor smaze 
function accept($data){
  
  $xml = new DOMDocument('1.0', 'UTF-8');  
  $xml->loadXML($data);
  
  $tokenvalue = "";
  $tokens = $xml->getElementsByTagName('resumptionToken');
  
  foreach ($tokens as $token) {
    $tokenvalue = $token->nodeValue;
  }
  if(strlen($tokenvalue)>0){
    file_put_contents("resumptiontoken.txt", $tokenvalue);
    return true;
  }
  else {
    if(file_exists("resumptiontoken.txt")){
      unlink("resumptiontoken.txt");
    }
    return false;
  }
}

//samotna funkce volajici knihovnu pro transformaci, vstupem jsou data z nuslu v promenne $xmlnodes
function xsltprocess($xmlnodes){

  //nacteme data jako domdocument
  $xml = new DOMDocument('1.0', 'UTF-8');
  $xml->loadXML($xmlnodes);
  
  //nacteme sablonu jako domdocument
  $xsl = new DOMDocument('1.0', 'UTF-8');
  $xsl->load("sablona-xsl2.xsl");

  //vytvorime instanci tridy xml_xslt2processor
  //zavolame funkci importStyleSheet z knihovny pro transformaci 
  //$proc = new XSLTProcessor;
  $proc = new XML_XSLT2Processor();
  $proc->importStyleSheet($xsl);
  
  //volame funkci transformtoxml, ktera vpodstate provadi spousteni saxonu, vysledek se ulozi do $result
  $result = $proc->transformToXML($xml);

  //kontrola, jestli nam prisel nejaky vysledek
  if(strlen($result)>0){
    return $result;
  }
  else {
    echo "neprobehla transformace!";
    exit;
  }
}

//provadi zapis dat do souboru, jehoz jmeno je v promenne $file 
function write($rdfxml, $file){    
   if (file_put_contents($file, $rdfxml)){
    return true;
   }
   else{
    return false;
   }   
}

?>
