<?php

namespace backup\Actions;

use backup\Manipulation\DeleteOld;
use backup\Manipulation\ZipFiles;
use backup\Manipulation\ZipFolder;
use Carbon\Carbon;
use edrard\Log\MyLog;
use Ifsnop\Mysqldump as IMysqldump;
use League\Flysystem\Filesystem;

abstract class AbsAction
{
    protected $config;
    protected $dst;
    protected $mysql;
    protected $local;
    protected $time_start;
    protected $time_end;
    protected $execution_time;
    protected $class;

    /**
    * put your comment there...
    *
    * @param array $config
    * @param Filesystem $dst
    * @param Filesystem $local
    * @param array $mysql
    */
    public function __construct(array $config, Filesystem $dst, Filesystem $local, array $mysql)
    {
        $this->config = $config;
        $this->dst = $dst;
        $this->mysql = $mysql;
        $this->local = $local;
        $this->checkLocalExist();
        $this->classGet();
        $this->startTime();
    }
    /**
    * put your comment there...
    *
    */
    public function classGet()
    {
        $class = explode('\\', self::class);
        $this->class =  array_pop($class);
    }
    /**
    * put your comment there...
    *
    */
    public function logRun()
    {
        MyLog::info('Starting backup process '.$this->class.' with config', $this->config, 'main');
    }
    /**
    * put your comment there...
    *
    */
    public function getConfig()
    {
        return $this-config;
    }
    /**
    * put your comment there...
    *
    */
    public function checkLocalExist()
    {
        $this->local->createDir($this->config['local']);
    }
    /**
    * put your comment there...
    *
    */
    protected function startTime()
    {
        $this->time_start = microtime(true);
    }
    /**
    * put your comment there...
    *
    */
    protected function endTime()
    {
        $this->time_end = microtime(true);
        $this->execution_time = $this->time_end - $this->time_start;
    }
    /**
    * put your comment there...
    *
    */
    protected function logEnd()
    {
        $this->endTime();
        MyLog::info('Backup time execution for '.$this->class.' is '.$this->execution_time, $this->config, 'main');
    }
    /**
    * put your comment there...
    *
    * @param string $src
    * @param string $dstfolder
    * @param array $exclude
    * @param string $parent
    */
    protected function rsync($src, $dstfolder, array $exclude = [], $parent = '')
    {
        MyLog::info('Sync started for config', $this->config, 'main');
        $exclude = $exclude === [] ? $this->config['exclude'] : $exclude;
        $this->dst->syncFiles($this->local, $src, $dstfolder, $exclude, $parent);
    }
    /**
    * put your comment there...
    *
    * @param string $loc
    * @param string $dst
    * @param string $name
    * @param int $increment
    */
    protected function archiveFiles($loc, $dst, $name, $increment = 0)
    {
        ZipFolder::zip($this->local, $loc, $dst, $increment, $name);
    }
    /**
    * put your comment there...
    *
    * @param string $filename
    * @param string $src
    * @param string $local
    * @param array $exclude
    */
    protected function increment($filename, $src, $local, $exclude)
    {
        $time = 0;
        $type = 'm';
        $exclude = $exclude;
        if (date('j') != $this->config['full_backup_date']) {
            $time = Carbon::now()->subDay()->subSeconds(5)->timestamp;
            $type = 'd';
            MyLog::info('Daily Increment Backup start', $this->config, 'main');
        } else {
            MyLog::info('Monthly Full Backup start', $this->config, 'main');
        }
        $this->archiveFiles($src, $local, $filename.'-'.$type, $time);
        return $type;
    }
    /**
    * put your comment there...
    *
    * @param string $filename
    * @param string $local
    * @param string $time
    */
    protected function deleteOld($filename, $local, $time)
    {
        DeleteOld::delete($this->local, $filename, $local, $time);
    }
    /**
    * put your comment there...
    *
    * @param string $localhost
    * @param string $user
    * @param string $pass
    * @param string $filename
    * @param string $local
    * @param array $mysqlbase
    */
    protected function mysqlDump($localhost, $user, $pass, $filename, $local, array $mysqlbase)
    {
        try {
            $files = [];
            foreach ($mysqlbase as $base) {
                $files[] = '/'.trim($local, '/').'/'.$base.'.sql';
                $dump = new IMysqldump\Mysqldump('mysql:host='.$localhost.';dbname='.$base, $user, $pass, ['lock-tables' => false]);
                $dump->start('/'.trim($local, '/').'/'.$base.'.sql');
            }
            ZipFiles::zip($filename, $local, $files);
            foreach ($files as $file) {
                $this->local->delete($file);
            }
        } catch (\Exception $error) {
            echo '[DumpMySQL] ' . $error->getMessage();
        }
    }
    /**
    * put your comment there...
    *
    * @param string $localhost
    * @param string $user
    * @param string $pass
    */
    protected function mysqlAllBases($localhost, $user, $pass)
    {
        $dbh = new \PDO("mysql:host=$localhost", $user, $pass);
        $dbs = $dbh->query('SHOW DATABASES');
        $return = [];
        while (($data_base = $dbs->fetchColumn(0)) !== false) {
            $return[] = $data_base;
        }
        return $return;
    }
}
