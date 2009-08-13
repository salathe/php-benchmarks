<?php

$file_ips = "../../ips.txt";
require "../../functions.php";
$server = unserialize(file_get_contents($file_ips));

if ($argc != 3) {
	echo "Usage: benchmark.php RECORDS CONCURRENCY\n";
	echo "RECORDS: The amount of user scenarios to be requested\n";
	echo "CONCURRENCY: The amount of concurrent users to be requesting\n";
	die();
}
echo "Benchmark has started\n";
echo "*********************\n";
passthru("ssh -f -oUserKnownHostsFile=/home/phpappbench/known_hosts -oStrictHostKeyChecking=no root@192.168.0.151 \"php /home/bench/benchmark.php $argv[1] $argv[2]\"");
echo "*********************\n";
echo "Benchmark has ended\n";
?>
