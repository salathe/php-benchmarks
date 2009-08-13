<?php
$ips = "../ips.txt";
$server = unserialize(file_get_contents($ips));

error_reporting(E_ALL ^ E_STRICT);

require "functions.php";
require "LoremIpsum.class.php";
require "Post.php";
require "Thread.php";
require "User.php";
require "Record.php";
$generator = new LoremIpsumGenerator();



$setting['avg_subject']      = 5;
$setting['subject_dev']      = 2;
$setting['avg_post']         = 400;
$setting['post_dev']         = 200;

/**
 * Make sure it's 100% at the end
 */

$setting['order']['view_topic']     = 45;
$setting['order']['reply']          = 25;
$setting['order']['thread']         = 25;
$setting['order']['register']       = 5;
asort($setting['order']);
?>
