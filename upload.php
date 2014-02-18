<?php
  
  function upload() {
    $graphStoreEndpoint = "http://localhost:8890/sparql-graph-crud-auth";
    $username = "dba";
    $password = "dba";
   
    $fileContent = file_get_contents(__DIR__ . "\\" . "1380032528.xml");
    $post_data = array("graph-uri" => "urn:test", "res-file" => $fileContent);     

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
    var_dump($response);
    
  }

upload();


?>
