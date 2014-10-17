<?php

include "uploadUP.php";


deletethem();


function deletethem() {

  $count = findthem();
  if ($count > 0){
    $querystring = file_get_contents(__DIR__ . "\\query-deleteUP.ru");

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
  $file = "query-selectUP.ru";
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
  $count = count($idlist);
  file_put_contents("logfileUP.txt", "\n pocet polozek ke smazani: " . $count, FILE_APPEND);
  
  return $count;  
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
