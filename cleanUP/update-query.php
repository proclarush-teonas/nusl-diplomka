<?php

include "uploadUP.php";


deletethem();


function deletethem() {
  $idlist = array();
  $idlist = findthem();
  //$idlist[] = 4; 


  foreach($idlist as $singleID) {

    $querystring = "
PREFIX dcterms: <http://purl.org/dc/terms/>
PREFIX biro: <http://purl.org/spar/biro/>
PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>

WITH <urn:nusl> DELETE {
?s dcterms:created ?o.
}
WHERE {
?s rdf:type biro:BibliographicRecord.
?s dcterms:created ?o.
?s dcterms:created ?p.
?s dcterms:identifier '". $singleID ."'.
FILTER (?o != ?p && ?p > ?o)
}";

    $response = uploadinit($querystring, 0);

    $respo = explode("<sparql", $response);
    $respon = "<sparql" . $respo[1];
    $xmlresponse = new DOMDocument('1.0', 'UTF-8');
    $xmlresponse->loadXML($respon);
    foreach ($xmlresponse->getElementsByTagName('literal') as $resstring) {
      file_put_contents("logfileUP.txt", "\n". $resstring->nodeValue, FILE_APPEND);
    }
  }
}

function findthem() {
  $file = "query-selectUP.txt";
  $res = uploadinit($file, 1);
  $resu = explode("<sparql", $res);
  $resul =  "<sparql" . $resu[1];
  
  $xmlresult = new DOMDocument('1.0', 'UTF-8');
   if ($xmlresult->loadXML($resul)){
    file_put_contents("logfileUP.txt", "\n dom xml nacteno", FILE_APPEND);
  }
  else {
    file_put_contents("logfileUP.txt", "\n nenactena data do dom xml", FILE_APPEND);
    exit;
  }
  $idlist = array();
  foreach ($xmlresult->getElementsByTagName('literal') as $singleID) {
    $idlist[] = $singleID->nodeValue;
  }
  
  //file_put_contents("logfileUP.txt", "\n pocet polozek v listu: " . count($idlist), FILE_APPEND);
  return $idlist;  
}

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
  if ($flag == 1){
    $data = file_get_contents(__DIR__ . "\\" . $file);
  }
  else {
    $data = $file;
  }
  $response = upload($endpoint, $user, $pword, $data, $graph);
  
  return $response;
  }


?>
