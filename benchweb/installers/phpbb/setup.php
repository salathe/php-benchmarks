<?php

$dir = "phpBB3";
require "../../config.php";

//Download phpBB3
shell_exec("wget http://d10xg45o6p6dbl.cloudfront.net/projects/p/phpbb/phpBB-3.0.5.tar.bz2");

if (!file_exists("phpBB-3.0.5.tar.bz2")) {
	die("Error downloading phpBB3. Check for another download source");
} else {
shell_exec("tar -jxvf phpBB-3.0.5.tar.bz2");

//Remove the install directory so it doesn't disturb
shell_exec("rm -r phpBB3/install");
}
//Apply the patch that modifies the needed files
shell_exec("patch -p1 -d phpBB3 < phpbb-3.0.5.patch ");

//Let's upload the directory to the www-root of the web server.
//We then, from the directory, requests the file "install.php" which will 
//setup the application. As an argument, we send the database ip.

scp_upload($dir,"/var/www/",$server["web"]);
ssh_exec("chown www-data:www-data /var/www/phpBB3/config.php",$server["web"]);

$db_name = "phpbb3";
$db_pass = "bench";
$db_user = "user";
$args = "db_ip={$server['db']}&db_name={$db_name}&db_pass={$db_pass}&db_user={$db_user}";
$db = mysql_connect($server['db'],$db_user,$db_pass) or die('Cannot connect to database');
$query = "DROP DATABASE phpbb3";
mysql_query($query);
$query = "CREATE DATABASE phpbb3";
mysql_query($query);

scp_upload("phpbb3.sql","/home/phpbb3.sql",$server["db"]);
ssh_exec("mysql -B phpbb3 < /home/phpbb3.sql",$server["db"]);
$res = file_get_contents("http://{$server['web']}/$dir/install.php?{$args}");

scp_upload("bench", "/home/", $server["client"]);

echo $res;
?>
