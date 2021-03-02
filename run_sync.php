<?php
ini_set('memory_limit', '2048M');
header("Content-type: text/html; charset=utf-8");
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('LOCAL_MAIN_DIR', __DIR__);

require __DIR__ . '/vendor/autoload.php';

$config = new backup\Config('ftp.json');
$config->setIncrementStart(3);
new backup\LogInitiation($config);
$new = new backup\Backup($config);
$new->run();
