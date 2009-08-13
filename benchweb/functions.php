<?php
// Uploads a file/dir from source to target @ host via SCP
function scp_upload($source,$target,$host) {
global $HOME_DIR;
$cmd = "scp -r -oUserKnownHostsFile={$HOME_DIR}known_hosts -oStrictHostKeyChecking=no {$source} root@{$host}:{$target}";
echo $cmd."\n";
return exec($cmd);
}

//Executes a command as root on $host
function ssh_exec($cmd,$host,$extra="") {
global $HOME_DIR;
$cmd = "ssh -f -oUserKnownHostsFile={$HOME_DIR}known_hosts -oStrictHostKeyChecking=no root@{$host} \"$cmd\" $extra";
echo $cmd."\n";
return exec($cmd);
}
?>
