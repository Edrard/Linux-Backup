<?php

namespace backup;

use Exc\NoDistinationException;
use Exc\NoInicializationException;
use Exc\SyncException;

class CliRunner
{
    const EXIT_SUCCESS = 0;
    const EXIT_CONFIG_ERROR = 1;
    const EXIT_BACKUP_ERROR = 2;
    const EXIT_SYNC_ERROR = 3;

    protected $configFile;
    protected $arguments;
    protected $baseDir;

    public function __construct($configFile, array $arguments, $baseDir)
    {
        $this->configFile = $configFile;
        $this->arguments = $arguments;
        $this->baseDir = rtrim($baseDir, '/\\');
    }

    public function run()
    {
        try {
            $config = new Config($this->configFile);
            $checkOnly = $this->hasArgument('--check-config');
            if (! $this->validateConfig($config)) {
                return self::EXIT_CONFIG_ERROR;
            }
            if ($checkOnly) {
                $this->writeLine('Config OK');
                return self::EXIT_SUCCESS;
            }
            $this->acquireBackupLock($this->baseDir.'/linux_backup.lock');
            new LogInitiation($config);
            $backup = new Backup($config);
            $backup->run();
            return self::EXIT_SUCCESS;
        } catch (\InvalidArgumentException $error) {
            return $this->backupError('[Config] '.$error->getMessage(), self::EXIT_CONFIG_ERROR);
        } catch (NoInicializationException $error) {
            return $this->backupError('[Config] '.$error->getMessage(), self::EXIT_CONFIG_ERROR);
        } catch (NoDistinationException $error) {
            return $this->backupError('[Config] '.$error->getMessage(), self::EXIT_CONFIG_ERROR);
        } catch (SyncException $error) {
            return $this->backupError('[Sync] '.$error->getMessage(), self::EXIT_SYNC_ERROR);
        } catch (\Throwable $error) {
            return $this->backupError('[Backup] '.$error->getMessage(), self::EXIT_BACKUP_ERROR);
        }
    }

    protected function hasArgument($name)
    {
        return in_array($name, $this->arguments, true);
    }

    protected function validateConfig(Config $config)
    {
        $validator = (new ConfigValidator($config))->validate();
        foreach ($validator->warnings() as $warning) {
            $this->writeLine('WARNING '.$warning);
        }
        if ($validator->isValid()) {
            return true;
        }
        foreach ($validator->errors() as $error) {
            $this->writeError('ERROR '.$error);
        }
        return false;
    }

    protected function acquireBackupLock($lockFile)
    {
        $handle = fopen($lockFile, 'c');
        if ($handle === false) {
            throw new \RuntimeException('Can not open lock file: '.$lockFile);
        }
        if (! flock($handle, LOCK_EX | LOCK_NB)) {
            fclose($handle);
            throw new \RuntimeException('Backup is already running. Lock file: '.$lockFile);
        }
        ftruncate($handle, 0);
        fwrite($handle, getmypid().' '.date('c').PHP_EOL);
        register_shutdown_function(function () use ($handle, $lockFile) {
            flock($handle, LOCK_UN);
            fclose($handle);
            if (file_exists($lockFile)) {
                unlink($lockFile);
            }
        });
    }

    protected function backupError($message, $code)
    {
        $this->writeError($message);
        return $code;
    }

    protected function writeLine($message)
    {
        echo $message.PHP_EOL;
    }

    protected function writeError($message)
    {
        if (defined('STDERR')) {
            fwrite(STDERR, $message.PHP_EOL);
            return;
        }
        $this->writeLine($message);
    }
}
