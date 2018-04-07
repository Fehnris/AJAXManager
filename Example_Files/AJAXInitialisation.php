<?php

session_start();

include '../Server/AJAXManagerServer.php';

//Database details to store optional AJAX Statistics
$DB_HOST = "";
$DB_USER = "";
$DB_PASSWORD = "";
$DB_NAME = "";
$DB_AJAX_TABLE_NAME = ""

$authObj = new AJAXAuthObject($DB_HOST, $DB_USER, $DB_PASSWORD, $DB_NAME, $DB_AJAX_TABLE_NAME, "");
$firstASK = $authObj->authorise_ISK();

echo ($firstASK !== "") ? $firstASK : "Authentication failure.  Access to this resource has been blocked";

?>