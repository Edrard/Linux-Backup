<?php

namespace backup;

class ConfigValidator
{
    protected $config;
    protected $errors = [];
    protected $warnings = [];
    protected $validTypes = ['now', 'time', 'increment'];
    protected $validBackupTypes = ['file', 'mysql'];
    protected $validDestinationTypes = ['ftp'];

    /**
    * put your comment there...
    *
    * @param Config $config
    */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
    * put your comment there...
    *
    */
    public function validate()
    {
        $this->errors = [];
        $this->warnings = [];
        $this->validateJobs();
        $this->validateLog();

        return $this;
    }

    /**
    * put your comment there...
    *
    */
    public function isValid()
    {
        return $this->errors === [];
    }

    /**
    * put your comment there...
    *
    */
    public function errors()
    {
        return $this->errors;
    }

    /**
    * put your comment there...
    *
    */
    public function warnings()
    {
        return $this->warnings;
    }

    /**
    * put your comment there...
    *
    */
    protected function validateJobs()
    {
        foreach ($this->config->returnActions() as $key => $job) {
            $this->validateJob($key, $job);
        }
    }

    /**
    * put your comment there...
    *
    * @param string $key
    * @param array $job
    */
    protected function validateJob($key, array $job)
    {
        $label = 'backup '.$key;
        $type = strtolower($job['type']);
        $typebackup = strtolower($job['typebackup']);

        if (! in_array($type, $this->validTypes, true)) {
            $this->error($label, 'unknown type: '.$job['type']);
        }
        if (! in_array($typebackup, $this->validBackupTypes, true)) {
            $this->error($label, 'unknown typebackup: '.$job['typebackup']);
        }
        if ($job['dstfolder'] === '') {
            $this->warning($label, 'dstfolder is empty, files will sync to destination root');
        }
        if ($typebackup === 'mysql' && $type !== 'time') {
            $this->warning($label, 'MySQL backups are run as time backups internally');
        }
        if ($typebackup === 'file') {
            $this->validateFileJob($label, $type, $job);
        } elseif ($typebackup === 'mysql') {
            $this->validateMysqlJob($label, $job);
        }
        $this->validateDestination($label, $job);
        $this->validateRetention($label, $type, $job);
    }

    /**
    * put your comment there...
    *
    * @param string $label
    * @param array $job
    */
    protected function validateFileJob($label, $type, array $job)
    {
        if ($job['src'] === []) {
            $this->error($label, 'file backup requires src');
            return;
        }
        if ($type !== 'now' && $job['filename'] === '') {
            $this->error($label, $type.' file backup requires filename');
        }
        foreach ($job['src'] as $src) {
            if (! is_dir($src)) {
                $this->warning($label, 'src does not exist or is not a directory: '.$src);
            }
        }
        foreach ($job['exclude'] as $exclude) {
            if (strpos($exclude, '..') !== false) {
                $this->error($label, 'exclude can not contain .. path segment: '.$exclude);
            }
        }
    }

    /**
    * put your comment there...
    *
    * @param string $label
    * @param array $job
    */
    protected function validateMysqlJob($label, array $job)
    {
        if ($job['filename'] === '') {
            $this->error($label, 'MySQL backup requires filename');
        }
        if ($job['mysqlbase'] === []) {
            $this->error($label, 'MySQL backup requires mysqlbase');
        }
        $mysql = $this->config->returnMysqlConfig($job['mysqlconfig']);
        if ($mysql === []) {
            $this->error($label, 'missing mysql config: '.$job['mysqlconfig']);
        } else {
            foreach (['host', 'user', 'pass'] as $field) {
                if (! isset($mysql[$field]) || ! trim($mysql[$field])) {
                    $this->error($label, 'mysql config '.$job['mysqlconfig'].' missing '.$field);
                }
            }
        }
        if (! is_array($job['mysqlbase_table_setup'])) {
            $this->error($label, 'mysqlbase_table_setup must be an object');
        }
    }

    /**
    * put your comment there...
    *
    * @param string $label
    * @param array $job
    */
    protected function validateDestination($label, array $job)
    {
        $destination = $this->config->returnConfig($job['dst']);
        if ($destination === []) {
            $this->error($label, 'missing destination config: '.$job['dst']);
            return;
        }
        if (! isset($destination['type']) || ! in_array($destination['type'], $this->validDestinationTypes, true)) {
            $this->error($label, 'unsupported destination type: '.(isset($destination['type']) ? $destination['type'] : ''));
        }
        foreach (['host', 'user', 'pass'] as $field) {
            if (! isset($destination[$field]) || ! trim($destination[$field])) {
                $this->error($label, 'destination '.$job['dst'].' missing '.$field);
            }
        }
    }

    /**
    * put your comment there...
    *
    * @param string $label
    * @param array $job
    */
    protected function validateRetention($label, $type, array $job)
    {
        if (! ctype_digit((string) $job['days'])) {
            $this->error($label, 'days must be a non-negative integer');
        }
        if (! ctype_digit((string) $job['months'])) {
            $this->error($label, 'months must be a non-negative integer');
        }
        if ($type === 'increment' && (! ctype_digit((string) $job['full_backup_date']) || (int) $job['full_backup_date'] < 1 || (int) $job['full_backup_date'] > 28)) {
            $this->warning($label, 'full_backup_date will be clamped to 1..28');
        }
        if (! is_dir($job['local'])) {
            $this->warning($label, 'local folder does not exist yet and will be created: '.$job['local']);
        }
    }

    /**
    * put your comment there...
    *
    */
    protected function validateLog()
    {
        $log = $this->config->returnLog();
        if (! isset($log['file']['dst']) || ! trim($log['file']['dst'])) {
            $this->error('log.file', 'dst is required');
        }
        if (isset($log['mail']['smtp']) && trim($log['mail']['smtp']) && (! isset($log['mail']['to']) || ! trim($log['mail']['to']))) {
            $this->warning('log.mail', 'smtp is set but recipient to is empty');
        }
    }

    /**
    * put your comment there...
    *
    * @param string $label
    * @param string $message
    */
    protected function error($label, $message)
    {
        $this->errors[] = '['.$label.'] '.$message;
    }

    /**
    * put your comment there...
    *
    * @param string $label
    * @param string $message
    */
    protected function warning($label, $message)
    {
        $this->warnings[] = '['.$label.'] '.$message;
    }
}
