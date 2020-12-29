<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require __DIR__ . '/vendor/autoload.php';

$string = file_get_contents("ftp.json");
$owner_user = fileowner('update.php');
$owner_group = filegroup('update.php');

$get_git_version = exec('git ls-remote https://github.com/Edrard/Linux-Backup.git HEAD');
preg_match('/^.*\s/iU', $get_git_version, $output_array);

$git_version = '';

if(isset($output_array[0])){
    $git_version = trim($output_array[0]);
}
$local_version = exec('git rev-parse HEAD');
if($local_version != $git_version){
    exec("git fetch --all");
    exec("git reset --hard origin/master");
    exec("git pull");
}

exec("COMPOSER_ALLOW_SUPERUSER=1 composer update");
exec("COMPOSER_ALLOW_SUPERUSER=0");

if($string && is_json($string)){
    file_write("ftp.json",$string);
}

exec('chown '.$owner_user.':'.$owner_group.' * -R');

function file_write($file,$string){
    $myfile = fopen($file, "w") or die("Unable to open file!");
    fwrite($myfile, $string);
    fclose($myfile);
}