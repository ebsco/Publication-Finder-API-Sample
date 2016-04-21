<?php
//session_start();
//Set Content-Type to application/xml
//header("Content-Type: application/xml");
require 'rest/EBSCOAPI.php';    

//On initialization
$lockfile = fopen("lock.txt","w+");
fclose($lockfile);
if (file_exists("token.txt")) {
       echo $_SESSION['resultxml'];     
} else {
	$tokenFile = fopen("token.txt","w+");
	$api = new EBSCOAPI();
	$result = $api->apiAuthenticationToken();
	fwrite($tokenFile, $result['authenticationToken']."\n");
	fwrite($tokenFile, $result['authenticationTimeout']."\n");
	fwrite($tokenFile, $result['authenticationTimeStamp']);
	fclose($tokenFile);
}

// Display the Basic Search by default
include 'basic_search.php';
/*if (isset($_REQUEST['result'])) {
    echo $_SESSION['resultxml'];
} else if (isset($_REQUEST['record'])) {
    echo $_SESSION['recordxml'];
} else {
    
}*/
?>
