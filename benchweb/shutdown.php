#!/usr/bin/php -q

<?php

/**
 * This file is used to shutdown (destroy) the virtual machines.
 * It removes the server ip and known hosts file as well.
 */

$file_ips = "ips.txt";
$known_hosts = "known_hosts";
$server = unserialize(file_get_contents($file_ips));

//Let's upload the directory to the www-root of the web server.
foreach($server as $hostname => $ip) {
exec("xm destroy {$hostname}"); 
}

unlink($file_ips);
unlink($known_hosts)
?>
