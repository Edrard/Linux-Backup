<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require __DIR__ . '/vendor/autoload.php';

$string = file_get_contents("ftp.json");

exec("git stash save \"Changes I don't want to commit yet\" ");
exec("git pull");
exec("git stash pop");

exec("composer update");

if($string && is_json($string)){
    $myfile = fopen("ftp.json", "w") or die("Unable to open file!");
    fwrite($myfile, $string);
    fclose($myfile);
}