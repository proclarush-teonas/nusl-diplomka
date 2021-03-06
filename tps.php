<?php

//includuju knihovnu ktera pripravi parametry saxonu, nalezne cesty k xml, xsl a definuje output
//uklada si postupne temp soubory ktere jsou po procesu smazany
//knihovnu jsem upravil, tak aby vyhovovala mym podminkam, tudiz nejde pouzit tu samou znovu stazenou
// prikaz __DR__ vyzaduje pouziti php verse 5.3 a vyssi
include (__DIR__. "/xml/XSLT2Processor.php");


// include souboru pro upload dat do virtuosa
include "upload.php";





run(0);


//funkce obsluhujici prubeh transformace, pomoci if podminek vypisuje stavy
function run($opakovani){

  file_put_contents("logfile.txt", "prubeh cislo: " . $opakovani . "\n", FILE_APPEND);
  if ($opakovani >= 10){
    exit;    
  }
  
  //parametr posledni aktualizace
  //$odkdy = time() - (60*60*24*7);
  $odkdy = file_get_contents("lastakt.txt");
  file_put_contents("logfile.txt", "cas pouzity pro request  - " . $odkdy . "\n", FILE_APPEND);

  $rtoken = "";
  //podminka pro prvotni nacitani cele databaze, pro aktualizace nebude potreba
  if(file_exists("resumptiontoken.txt")){
    $rtoken = file_get_contents("resumptiontoken.txt");
  }
  if (strlen($rtoken)>0){    
    $data = getdata($rtoken, "");
    file_put_contents("logfile.txt", "token pouzity ke stazeni dat  - " . $rtoken . "\n", FILE_APPEND);
  }
  //do else bude vstupovat prubezna aktualizace, musi byt pouzit parametr 'from' v requestu na nusl
  //po transformaci cele databaze by se mela postarat funkce accept o smazani souboru resumptiontoken.txt
  else {     
    //odkomentovat na uplne prvni spusteni    !!!!!!!!!!!!!!!!!
    //$data = getdata("", "");
    $data = getdata("", $odkdy);
    $cont = date('Y-m-d') . "T" . date('H:i:s') . "Z";
    file_put_contents("lastakt.txt", $cont);
    
    //$data = file_get_contents("temporary.xml");       //viz komentar pod
  }

  //kvuli zaznamu, ktery nejni validni  !
  //file_put_contents("temporary.xml", $data);
  //exit;
  
  //podminka ktera po nacteni dat zapise do souboru atualni resumption token, nebo pokud neexistuje, tak soubor smaze
  if(!accept($data)){    
    file_put_contents("logfile.txt", "token nebyl zapsan do souboru!  - " . date('Y-m-d') . "\n", FILE_APPEND);
    //exit;    
  }
  //pojmenovani souboru tak, aby kazdy mel jine jmeno, zatim pomoci casove znamky. 
  //ukladani je pouze pro informaci a zalohu, protoze se rovnou uploadnou do virtuosa 
  $file = time() . ".xml";
  
  //podminka volajici funkci write, ktera zapise do souboru data transformovana funkci xsltprocess  
  if(!write(xsltprocess($data), $file)){
    file_put_contents("logfile.txt", "neprobehl zapis dat do souboru!  - " . date('Y-m-d') . "\n", FILE_APPEND);
    exit;
  }
  else {
    file_put_contents("logfile.txt", "data ulozena do souboru: " . $file . "\n", FILE_APPEND);
  }
  
  //nastaveni parametru pro upload a jeho spusteni
  if(!uploadinit($file)){
    exit;
  }
  else {
    $istoken = file_get_contents("resumptiontoken.txt");
    if (strlen($istoken) >0) {
      $opakovani += 1;
      run($opakovani);
    }
    else {
      exit;
    }
  }
}

//ve funkci se nastavuji parametry uploadu dat
function uploadinit($file){
  if($configfile = fopen("config.ini", "r")){
    
    $endpointa = explode(" ", fgets($configfile));
    $endpoint = trim($endpointa[1]);    
    $usera = explode(" ", fgets($configfile));
    $user = trim($usera[1]);    
    $pworda = explode(" ", fgets($configfile));
    $pword = trim($pworda[1]);    
    $grapha = explode(" ", fgets($configfile));
    $graph = trim($grapha[1]);      
    fclose($configfile); 
    
       
  }
  else {
    file_put_contents("logfile.txt", "nepodarila se nacist konfigurace uploadu \n", FILE_APPEND);
    exit;
  }
  
  $data = file_get_contents(__DIR__ . "\\" . $file);
  $response = upload($endpoint, $user, $pword, $data, $graph);
  
  if (strpos($response, "200 OK") !== false){
    file_put_contents("logfile.txt", "data nahrana do virtuosa \n", FILE_APPEND);
    return true;    
  }
  else {
    file_put_contents("logfile.txt", "chyba pri nahravani dat do virtuosa \n" . $response, FILE_APPEND);
    return false;
  }
}

//funkce nacitajici zaznamy z nuslu, budto od zacatku, 
//nebo od mista predchoziho ukonceni podle resumption tokenu, nebo od daneho casu (v pripade aktualizace)
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
   file_put_contents("logfile.txt", "nepovedlo se ziskat data!  - " . date('Y-m-d') . "\n", FILE_APPEND);
   exit;
  }

}

//kontroluje resumption token a zapisuje ho do souboru, kdyz neexistuje, tak se soubor smaze 
function accept($data){
  
  $xml = new DOMDocument('1.0', 'UTF-8');  
  try {
    $xml->loadXML($data);
  }
  catch(Exception $e) {
    file_put_contents("logfile.txt", "nevalidni xml data. Data ulozena do souboru: temporary.xml \n", FILE_APPEND);
    file_put_contents("temporary.xml", $data);
    exit;
  }
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
    file_put_contents("logfile.txt", "neprobehla transformace!  - " . date('Y-m-d') . "\n", FILE_APPEND);
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
