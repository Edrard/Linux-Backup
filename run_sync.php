<?php
ini_set('memory_limit', '2048M');
header("Content-type: text/html; charset=utf-8");
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('LOCAL_MAIN_DIR', __DIR__);

require __DIR__ . '/vendor/autoload.php';

$runner = new backup\CliRunner(LOCAL_MAIN_DIR.'/ftp.json', isset($argv) ? $argv : [], LOCAL_MAIN_DIR);
exit($runner->run());
