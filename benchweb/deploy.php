#!/usr/bin/php -q

<?php

/**
 *  This file is used to deploy the three VM's.
 *  The servers that are created are:
 *  Webserver with the argument IP (apache and php)
 *  Client with the argument IP + 1 (php5 with curl)
 *  DB with the argument IP + 2 (mysql-server)
 *
 *  The client will serve as the benchmarking server when
 *  an application has been installed, such as phpBB3.
 */

require "config.php";

$ip = $argv[1];
if ($argc != 2) {
	die("deploy.php START-IP");
}

$server = array();
$server['web']    = $ip;
$server['client'] = long2ip((ip2long($ip)+1));
$server['db']     = long2ip((ip2long($ip)+2));

foreach ($server as $hostname => $ip) {
	shell_exec("xen-create-image --hostname={$hostname} --ip={$ip} --netmask={$netmask} --gateway={$gateway} --dir=/home/xen/ --force --role=../../..$ROLES_DIR$hostname");
}

foreach ($server as $hostname => $ip) {
	shell_exec("xm create {$hostname}.cfg");
}
sleep(3);
$running = false;
echo "Checking connection\n";
$count = 0;
while($count < 30) {
	$running = true;
	foreach ($server as $hostname => $ip) {
		if (!@fsockopen($ip,22,$errnum,$errstr,2)) {
		$running = false;
		//echo "Server $hostname, ip $ip is not up yet.";
		sleep(2);
		}
	}
$count++;
}

//Serialize the ips and hostname to a file that can be used later
$file_ips = $HOME_DIR."ips.txt";
$fh = fopen($file_ips,'w');
fwrite($fh,serialize($server));
fclose($fh);
if ($running) {
	echo "The servers are running";
	include "{$HOME_DIR}setup.php";
} else {
	echo "The servers are not running";
}


?>
