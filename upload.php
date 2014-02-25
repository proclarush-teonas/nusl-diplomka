<?php
  
  function upload($endpoint, $user, $pword, $data, $graph) {
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
    file_put_contents("logfile.txt", $response . "************** \n", FILE_APPEND);
    
  }


?>
