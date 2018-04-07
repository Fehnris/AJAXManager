<?php

session_start();

include '../Server/AJAXManagerServer.php';

//Database Authentication and database selection variables
$DB_HOST = "";
$DB_USER = "";
$DB_PASSWORD = "";
$DB_NAME = "";
$DB_AJAX_TABLE_NAME = "";
 
$authObj = new AJAXAuthObject($DB_HOST, $DB_USER, $DB_PASSWORD, $DB_NAME, $DB_AJAX_TABLE_NAME, "");
$ASK = $authObj->authorise_ASK();

//AJAX query (q) post variable.
$q = $_REQUEST["q"];
$returnObj = (object) array("query"=>"", "token"=>"");

if($ASK !== "") {
	$returnObj["token"] = $ASK;
	$returnObj["query"] = ""; //Your data to be sent back goes here.
}

// Output "no suggestion" if no hint was found or output correct values
$pageOutput = json_encode($returnObj);
echo $pageOutput;

?>