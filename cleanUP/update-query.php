<?php

//include souboru pro exekuci query
include "uploadUP.php";

//volani metody mazani zaznamu s cilem najit vicenasobne vyskyty dcterms:created v grafu a smazat je
deletethem("query-selectUP.ru", "query-deleteUP.ru");
//volani metody mazani zaznamu s cilem najit trojice obsahujici prazdny objekt a smazat je
deletethem("query-select-empty.ru", "query-delete-empty.ru");
//volani metody mazani zaznamu s cilem najit trojice s neplatnymi jmeny v dcterms:contributor a smazat je
deletethem("query-select-contributor.ru", "query-delete-contributor.ru");


//funkce ktera nacita query, necha ho zpracovat pomoci funkce uploadinit a vypisuje vysledek do logu
function deletethem($queryS, $queryD) {

//$count obsahuje vysledek po volani metody findthem, ktery indikuje jestli existuji nejake zaznamy ke smazani
  $count = findthem($queryS);
  if ($count > 0){
    $querystring = file_get_contents(__DIR__ . "\\" . $queryD);

    $response = handleresponse(uploadinit($querystring, 0));

    $xmlresponse = new DOMDocument('1.0', 'UTF-8');
    $xmlresponse->loadXML($response);
    foreach ($xmlresponse->getElementsByTagName('literal') as $resstring) {
      file_put_contents("logfileUP.txt", "\n". $resstring->nodeValue, FILE_APPEND);
    }
  }
}

//funkce ktera podle query vyhledava zaznamy pozdeji urcene ke smazani a jako vysledek vraci jejich pocet
function findthem($queryS) {
  $file = $queryS;
  $res = handleresponse(uploadinit($file, 1));
  
  $xmlresult = new DOMDocument('1.0', 'UTF-8');
   if ($xmlresult->loadXML($res)){
    file_put_contents("logfileUP.txt", "\n odpoved na ".$queryS." nactena do xml", FILE_APPEND);
  }
  else {
    file_put_contents("logfileUP.txt", "\n nenactena data do dom xml", FILE_APPEND);
    exit;
  }
  $idlist = array();
  if($queryS == "query-select-contributor.ru"){
    foreach ($xmlresult->getElementsByTagName('uri') as $singleID) {
      $idlist[] = $singleID->nodeValue;
    }
  }
  else {
    foreach ($xmlresult->getElementsByTagName('literal') as $singleID) {
      $idlist[] = $singleID->nodeValue;
    }
  }
  $count = count($idlist);
  file_put_contents("logfileUP.txt", "\n pocet polozek ke smazani: " . $count, FILE_APPEND);
  
  return $count;  
}

//nacitani parametru z config souboru pro nasledujici volani funkce upload z vyse includovaneho souboru uploadUP.php
function uploadinit($file, $flag){
  if($configfile = fopen("configUP.ini", "r")){
    
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
    file_put_contents("logfileUP.txt", "nepodarila se nacist konfigurace uploadu -update \n", FILE_APPEND);
    exit;
  }
  //promenna $flag pouze urcuje jakym zpusobem se ziska query string
  if ($flag == 1){
    $data = file_get_contents(__DIR__ . "\\" . $file);
  }
  else {
    $data = $file;
  }
  //volani funkce upload s nactenymi parametry a query
  $response = upload($endpoint, $user, $pword, $data, $graph);
  
  return $response;
}

//pomocna funkce ktera prevadi odpoved od virtuosa do validniho xml
function handleresponse($response){
  $respo = explode("<sparql", $response);
  $respon = "<sparql" . $respo[1];
  return $respon;
}

?>
