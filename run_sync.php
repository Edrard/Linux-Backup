<?php
ini_set('memory_limit', '2048M');
header("Content-type: text/html; charset=utf-8");
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('LOCAL_MAIN_DIR', __DIR__);
define('EXIT_CONFIG_ERROR', 1);
define('EXIT_BACKUP_ERROR', 2);
define('EXIT_SYNC_ERROR', 3);

require __DIR__ . '/vendor/autoload.php';

try {
    $config = new backup\Config('ftp.json');
    if (has_argument('--check-config')) {
        check_config($config);
    }
    acquire_backup_lock(LOCAL_MAIN_DIR.'/linux_backup.lock');
    new backup\LogInitiation($config);
    $new = new backup\Backup($config);
    $new->run();
} catch (\InvalidArgumentException $error) {
    backup_error('[Config] '.$error->getMessage(), EXIT_CONFIG_ERROR);
} catch (\Exc\NoInicializationException $error) {
    backup_error('[Config] '.$error->getMessage(), EXIT_CONFIG_ERROR);
} catch (\Exc\NoDistinationException $error) {
    backup_error('[Config] '.$error->getMessage(), EXIT_CONFIG_ERROR);
} catch (\Exc\SyncException $error) {
    backup_error('[Sync] '.$error->getMessage(), EXIT_SYNC_ERROR);
} catch (\Throwable $error) {
    backup_error('[Backup] '.$error->getMessage(), EXIT_BACKUP_ERROR);
}

function acquire_backup_lock($lock_file)
{
    $handle = fopen($lock_file, 'c');
    if ($handle === false) {
        throw new \RuntimeException('Can not open lock file: '.$lock_file);
    }
    if (! flock($handle, LOCK_EX | LOCK_NB)) {
        fclose($handle);
        throw new \RuntimeException('Backup is already running. Lock file: '.$lock_file);
    }
    ftruncate($handle, 0);
    fwrite($handle, getmypid().' '.date('c').PHP_EOL);
    register_shutdown_function(function () use ($handle, $lock_file) {
        flock($handle, LOCK_UN);
        fclose($handle);
        if (file_exists($lock_file)) {
            unlink($lock_file);
        }
    });
}

function has_argument($name)
{
    global $argv;
    return is_array($argv) && in_array($name, $argv, true);
}

function check_config(\backup\Config $config)
{
    $validator = (new \backup\ConfigValidator($config))->validate();
    foreach ($validator->warnings() as $warning) {
        echo 'WARNING '.$warning.PHP_EOL;
    }
    if ($validator->isValid()) {
        echo 'Config OK'.PHP_EOL;
        exit(0);
    }
    foreach ($validator->errors() as $error) {
        if (defined('STDERR')) {
            fwrite(STDERR, 'ERROR '.$error.PHP_EOL);
        } else {
            echo 'ERROR '.$error.PHP_EOL;
        }
    }
    exit(EXIT_CONFIG_ERROR);
}

function backup_error($message, $code)
{
    if (defined('STDERR')) {
        fwrite(STDERR, $message.PHP_EOL);
    } else {
        echo $message.PHP_EOL;
    }
    exit($code);
}
