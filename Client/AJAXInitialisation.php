<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
//testing

session_start();

//PHP library and PHP global settings files to include
require __DIR__.'/../../../Settings/Settings.php';
require __DIR__.'/../Server/AJAXManagerServer.php';

//Database Authentication and database selection variables
$DB_HOST = $mysqlDataBaseDetails['DB_HOST'];
$DB_USER = $mysqlDataBaseDetails['DB_USER'];
$DB_PASSWORD = $mysqlDataBaseDetails['DB_PASS'];
$DB_NAME = $mysqlDataBaseDetails['DB_NAME'];

$authObj = new AJAXManagerServer($DB_HOST, $DB_USER, $DB_PASSWORD, $DB_NAME, "PHPScriptAuth", "");
//echo "HOST".$DB_HOST;
echo $authObj->Authorise();

?>