<?php
ini_set('memory_limit', '2048M');
header("Content-type: text/html; charset=utf-8");
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('LOCAL_MAIN_DIR', __DIR__);   

require __DIR__ . '/vendor/autoload.php';

define('FULL_INCREMENT',isset($argv[1]) && $argv[1] == 'full' ? 1 : -1);
 
  
$config = new backup\Config('ftp.json');
$new = new backup\Backup($config);
$new->run();