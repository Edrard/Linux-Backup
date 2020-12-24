<?php

$string = file_get_contents("ftp.json");

exec("git pull");
exec("composer update");

if($string && is_json($string)){
    $myfile = fopen("ftp.json", "w") or die("Unable to open file!");
    fwrite($myfile, $string);
    fclose($myfile);
}