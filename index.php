<?php 
/**
=============================================================================================
* APP NAME: Publication Finder API Sample
* DESCRIPTION: this app is a simple sample demo which uses an alphabetical browse option and 
* a search box to find publications through the API.
* TAGS: publication finder, pf, api, sample, a to z, a-z, atoz, a-to-a, Alphabetical Browse
* CUSTOMER PARAMETERS:
* EBSCO PARAMETERS:
* EADMIN IFRAME URL: http://widgets.ebscohost.com/prod/ftf-atoz/index.php
* AUTHOR & EMAIL: 	Pilar Arriola - parriola@ebsco.com
* DATE ADDED: 2015-12-26
* DATE MODIFIED: 		
* LAST CHANGE DESCRIPTION:
=============================================================================================
**/
error_reporting(E_ERROR | E_PARSE);
require 'rest/EBSCOAPI.php';
//On initialization
$lockfile = fopen("lock.txt","w+");
fclose($lockfile);
if (file_exists("token.txt")) {    
} else {
	$tokenFile = fopen("token.txt","w+");
	$api = new EBSCOAPI();
	//Get information about the authentication
    $result = $api->apiAuthenticationToken();
	fwrite($tokenFile, $result['authenticationToken']."\n");
	fwrite($tokenFile, $result['authenticationTimeout']."\n");
	fwrite($tokenFile, $result['authenticationTimeStamp']);
	fclose($tokenFile);
}

// Display the Basic Search by default
include 'basic_search.php';
?>