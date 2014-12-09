<?php

include "upload.php";
run();

function run(){
  $data = getdata();
  uploadinit($data);
}

function uploadinit($data){
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
    file_put_contents("logfile.txtUP", "nepodarila se nacist konfigurace uploadu \n", FILE_APPEND);
    exit;
  }
  
  $response = upload($endpoint, $user, $pword, $data, $graph);
  
  if (strpos($response, "200 OK") !== false){
    file_put_contents("logfileUP.txt", "silk-link data nahrana do virtuosa \n", FILE_APPEND);   
  }
  else {
    file_put_contents("logfileUP.txt", "chyba pri nahravani silk-link dat do virtuosa \n", FILE_APPEND);
  }
}

function getdata(){                                                                                  
  $base = file_get_contents("c:\Documents and Settings\jim\.silk\output\LinkedKeywords.nt");
  //$base = file_get_contents("C:/wamp/www/nusl/temporal2b.nt");                           
  if(strlen($base)>0){
    $base = "@prefix owl: <http://www.w3.org/2002/07/owl#>. \n" .$base;  
    return $base;
  }
  else {
    file_put_contents("logfileUP.txt", "nepodarilo se nacist silk-link data \n", FILE_APPEND);
    exit;
  }  
}


?>
