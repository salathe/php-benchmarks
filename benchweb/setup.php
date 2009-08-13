<?php

/**
 * Setup.php will change configuration-files on the VM servers
 * This file is included in the deploy.php file and shouldn't
 * be called individually.
 */

foreach ($server as $hostname=>$ip ) {
ssh_exec("mkdir /dev/pts",$ip);
ssh_exec("mount -t devpts devpts /dev/pts",$ip);
if ($hostname == "web") {
scp_upload("{$MACHINES_DIR}/web/httpd.conf","/etc/apache/httpd.conf",$ip);
scp_upload("{$MACHINES_DIR}/web/limitapache","/usr/sbin/limitapache",$ip);
ssh_exec("/etc/init.d/apache restart",$ip);
ssh_exec("/usr/sbin/limitapache > /dev/null",$ip,"> /dev/null");
} else if ($hostname == "client") {
scp_upload("{$HOME_DIR}ips.txt","/home/ips.txt",$ip);
} else if ($hostname == "db") {
echo "DB";
ssh_exec("sed -i 's/bind-address/#bind-address/' /etc/mysql/my.cnf",$ip);
ssh_exec("mysql -uroot -e \\\"CREATE USER 'user'@'%' IDENTIFIED BY 'bench'\\\"",$ip);
ssh_exec("mysql -uroot -e \\\"GRANT ALL PRIVILEGES ON *.* TO 'user'@'%' WITH GRANT OPTION\\\"",$ip);
ssh_exec("/etc/init.d/mysql restart",$ip);
}
}


?>
