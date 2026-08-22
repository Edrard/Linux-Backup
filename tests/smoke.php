<?php

define('LOCAL_MAIN_DIR', dirname(__DIR__));

require dirname(__DIR__).'/vendor/autoload.php';

use Flysystem\SyncFiles;
use backup\CliRunner;
use backup\Config;
use backup\ConfigValidator;
use backup\Manipulation\ZipFolder;

$tmp = sys_get_temp_dir().'/linux_backup_smoke_'.getmypid();
if (is_dir($tmp)) {
    removeDir($tmp);
}
mkdir($tmp);
mkdir($tmp.'/src');
mkdir($tmp.'/local');

$configFile = $tmp.'/ftp.json';
file_put_contents($configFile, json_encode([
    'backup' => [
        '1' => [
            'src' => $tmp.'/src,'.$tmp.'/local',
            'dstfolder' => '/site/files',
            'local' => $tmp.'/local',
            'type' => 'increment',
            'days' => '0',
            'months' => '2',
            'full_backup_date' => '7',
            'filename' => 'files',
            'fileinc' => 'd-m-Y',
            'typebackup' => 'file',
            'exclude' => 'cache logs/tmp',
            'mysqlbase' => '',
            'mysqlbase_exclude' => '',
            'mysqlbase_table_setup' => [],
            'mysqlconfig' => '',
            'dst' => '1',
        ],
        '2' => [
            'src' => '',
            'dstfolder' => '/site/mysql',
            'local' => $tmp.'/local',
            'type' => 'time',
            'days' => '5',
            'months' => '0',
            'filename' => 'mysql',
            'fileinc' => 'd-m-Y',
            'typebackup' => 'mysql',
            'exclude' => '',
            'mysqlbase' => '+',
            'mysqlbase_exclude' => 'otrs test_db',
            'mysqlbase_table_setup' => [],
            'mysqlconfig' => '1',
            'dst' => '1',
        ],
    ],
    'config' => [
        '1' => [
            'type' => 'ftp',
            'host' => 'example.com',
            'user' => 'user',
            'pass' => 'pass',
        ],
    ],
    'mysql' => [
        '1' => [
            'host' => 'localhost',
            'user' => 'root',
            'pass' => 'pass',
        ],
    ],
    'log' => [
        'file' => [
            'dst' => $tmp.'/logs',
            'full' => '1',
        ],
        'mail' => [],
    ],
]));

try {
    $config = new Config($configFile);
    $jobs = $config->returnActions();

    assertSame([$tmp.'/src', $tmp.'/local'], $jobs['1']['src'], 'Config splits comma-separated src');
    assertSame(['cache', 'logs/tmp'], $jobs['1']['exclude'], 'Config parses file excludes');
    assertSame(['+'], $jobs['2']['mysqlbase'], 'Config parses mysqlbase');
    assertSame(['otrs', 'test_db'], $jobs['2']['mysqlbase_exclude'], 'Config parses mysqlbase_exclude');

    $validator = (new ConfigValidator($config))->validate();
    assertSame(true, $validator->isValid(), 'ConfigValidator accepts smoke config');

    $syncFiles = new SyncFiles();
    setObjectProperty($syncFiles, 'exclude', ['cache', 'logs/tmp']);
    assertSame(true, callObjectMethod($syncFiles, 'isExcluded', ['cache/file.txt']), 'SyncFiles excludes nested folder files');
    assertSame(true, callObjectMethod($syncFiles, 'isExcluded', ['logs/tmp']), 'SyncFiles excludes exact folder');
    assertSame(false, callObjectMethod($syncFiles, 'isExcluded', ['logs/app.log']), 'SyncFiles keeps sibling folders');

    setStaticProperty(ZipFolder::class, 'exclude', ['cache', 'logs/tmp']);
    assertSame(true, callStaticMethod(ZipFolder::class, 'isExcluded', ['logs/tmp/debug.log']), 'ZipFolder excludes nested folder files');
    assertSame(false, callStaticMethod(ZipFolder::class, 'isExcluded', ['logs/debug.log']), 'ZipFolder keeps sibling folders');

    ob_start();
    $runnerCode = (new CliRunner($configFile, ['tests/smoke.php', '--list-jobs'], dirname(__DIR__)))->run();
    $listOutput = ob_get_clean();
    assertSame(CliRunner::EXIT_SUCCESS, $runnerCode, 'CliRunner --list-jobs exits successfully');
    assertContains('[1] file/increment', $listOutput, 'CliRunner lists file job');
    assertContains('exclude: cache, logs/tmp', $listOutput, 'CliRunner lists file excludes');
    assertContains('mysqlbase_exclude: otrs, test_db', $listOutput, 'CliRunner lists MySQL excludes');

    echo 'Smoke tests OK'.PHP_EOL;
} finally {
    removeDir($tmp);
}

function assertSame($expected, $actual, $message)
{
    if ($expected !== $actual) {
        throw new RuntimeException($message.'; expected '.var_export($expected, true).', got '.var_export($actual, true));
    }
}

function assertContains($needle, $haystack, $message)
{
    if (strpos($haystack, $needle) === false) {
        throw new RuntimeException($message.'; missing '.$needle.' in '.var_export($haystack, true));
    }
}

function setObjectProperty($object, $property, $value)
{
    $reflection = new ReflectionProperty(get_class($object), $property);
    $reflection->setAccessible(true);
    $reflection->setValue($object, $value);
}

function setStaticProperty($class, $property, $value)
{
    $reflection = new ReflectionProperty($class, $property);
    $reflection->setAccessible(true);
    $reflection->setValue($value);
}

function callObjectMethod($object, $method, array $arguments)
{
    $reflection = new ReflectionMethod(get_class($object), $method);
    $reflection->setAccessible(true);
    return $reflection->invokeArgs($object, $arguments);
}

function callStaticMethod($class, $method, array $arguments)
{
    $reflection = new ReflectionMethod($class, $method);
    $reflection->setAccessible(true);
    return $reflection->invokeArgs(null, $arguments);
}

function removeDir($path)
{
    if (! is_dir($path)) {
        return;
    }
    foreach (array_diff(scandir($path), ['.', '..']) as $item) {
        $itemPath = $path.'/'.$item;
        if (is_dir($itemPath)) {
            removeDir($itemPath);
        } else {
            unlink($itemPath);
        }
    }
    rmdir($path);
}
