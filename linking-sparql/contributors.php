<?php

include "uploadUP.php";
run();

function run(){
  $data = getdata();
  $result = transformdata($data);
  $response = uploadinit($result, "configCont.ini", 2);
  //var_dump($response);
}

function uploadinit($data, $config, $int){
  if($configfile = fopen($config, "r")){
    
    $endpointa = explode(" ", fgets($configfile));
    $endpoint = trim($endpointa[1]);    
    $usera = explode(" ", fgets($configfile));
    $user = trim($usera[1]);    
    $pworda = explode(" ", fgets($configfile));
    $pword = trim($pworda[1]);    
    $grapha = explode(" ", fgets($configfile));
    $graph = "";
    if ($int > 1){
    $graph = trim($grapha[1]);
    }      
    fclose($configfile); 
  }
  else {
    file_put_contents("logfile.txtUP", "nepodarila se nacist konfigurace uploadu \n", FILE_APPEND);
    exit;
  }
  if($int < 2){
    $response = upload($endpoint, $user, $pword, $data, $graph);
  }
  else{
    $response = upload2($endpoint, $user, $pword, $data, $graph);
  }

  return $response;
}

function getdata(){                                                                                  
   $data = file_get_contents(__DIR__ . "\\query-select-contributors-distinct.ru");
   //$res = uploadinit($data);
   
   $res = handleresponse(uploadinit($data, "configUP.ini", 1));
   if(strlen($res)>0){
   
    return $res;
   }
   else {
     file_put_contents("logfileUP.txt", "nenactena data z db \n", FILE_APPEND);
     exit;
   }  
}

function handleresponse($response){
  $respo = explode("<sparql", $response);
  $respon = "<sparql" . $respo[1];
  return $respon;
}


function transformdata($data) {

    $xmlnodes = new DOMDocument('1.0', 'UTF-8');
    $xmlnodes->loadXML($data);
    $tosave = "@prefix rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#> ." . PHP_EOL . "@prefix foaf: <http://xmlns.com/foaf/0.1/> ." . PHP_EOL;
    $pocitadlo = 0;

    foreach ($xmlnodes->getElementsByTagName('uri') as $resstring) {
      $tosave .= "<" . $resstring->nodeValue . "> <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://xmlns.com/foaf/0.1/Person> ." . PHP_EOL;
      $pom0 = explode("contributors/", $resstring->nodeValue);

      $pom1 = "<" . $resstring->nodeValue . "> <http://xmlns.com/foaf/0.1/name> \"" . urldecode($pom0[1]) . "\" ." . PHP_EOL; 
      $tosave .= $pom1;      
      $pocitadlo += 1;
    }
    file_put_contents("logfileUP.txt", "\n kontributoru vytvoreno v novem grafu: " . $pocitadlo, FILE_APPEND);  
    return $tosave;
    

}

function upload2($endpoint, $user, $pword, $data, $graph) {
    $graphStoreEndpoint = $endpoint;
    $username = $user;
    $password = $pword;
   
    $fileContent = $data;
    $post_data = array("graph-uri" => $graph, "res-file" => $fileContent);     

    $options = array(
        CURLOPT_URL            => $graphStoreEndpoint,
        CURLOPT_HEADER         => true,
        CURLOPT_ENCODING       => "",    
        CURLOPT_VERBOSE        => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERPWD        => $username . ":" . $password,
        CURLOPT_HTTPAUTH       => CURLAUTH_DIGEST,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($post_data)  
    );
        
    $ch = curl_init();
    curl_setopt_array( $ch, $options );

    $response = curl_exec( $ch );
    //file_put_contents("logfile.txt", $response . "************** \n", FILE_APPEND);
    return $response;
    
  } 

?>
