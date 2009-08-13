<?php
require "functions.php";

/**
 * This is the configuration file for web applications benchmark.
 * These fields should be modified to your local network-settings
 * to make it possible to deploy the servers.
 *
 **/

// Your local gateway
$gateway = "192.168.0.1";

// Your local netmask
$netmask = "255.255.255.0";

//The directory where this file and the deploy files are inside.
$HOME_DIR     = "/home/php/benchweb/";
$MACHINES_DIR = $HOME_DIR."machines/";
$ROLES_DIR    = $HOME_DIR."roles/";

if (file_exists($HOME_DIR."/ips.txt"))
{
$server = unserialize(file_get_contents($HOME_DIR."/ips.txt"));
}


?>
