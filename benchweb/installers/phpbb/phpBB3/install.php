<?php
//This file will install phpBB3 to the webserver with an mysql connection
//to $_GET['db_ip'] along with $_GET['db_name'],$_GET['db_pass'],$_GET['db_user']
error_reporting(E_ALL);

$db_ip    = $_GET['db_ip'];
$db_name  = $_GET['db_name'];
$db_pass  = $_GET['db_pass'];
$db_user  = $_GET['db_user'];
$hostname = $_SERVER['SERVER_ADDR'];
$data = file_get_contents("configdef.php");
$data = str_replace("sqlhost",$db_ip,$data);
$data = str_replace("sqldb",$db_name,$data);
$data = str_replace("sqluser",$db_user,$data);
$data = str_replace("sqlpass",$db_pass,$data);
$data = str_replace("domain",$hostname,$data);
file_put_contents("config.php",$data);
?>
