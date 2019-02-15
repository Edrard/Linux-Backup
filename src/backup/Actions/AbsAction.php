<?php
namespace backup\Actions;

use League\Flysystem\Filesystem;
use backup\Manipulation\DeleteOld;
use backup\Manipulation\ZipFolder;
use backup\Manipulation\ZipFiles;
use Carbon\Carbon;
use edrard\Log\MyLog;
use Ifsnop\Mysqldump as IMysqldump;

Abstract Class AbsAction
{
    protected $config;
    protected $dst;
    protected $mysql;
    protected $local;
    protected $time_start;
    protected $time_end;
    protected $execution_time;
    protected $class;

    function __construct(array $config, Filesystem $dst, Filesystem $local, array $mysql){
        $this->config = $config;
        $this->dst = $dst;
        $this->mysql = $mysql;
        $this->local = $local;
        $this->checkLocalExist();
        $this->classGet();
        $this->startTime();
    } 
    function classGet(){
        $class = explode('\\', get_class($this));
        $this->class =  array_pop($class);
    }
    function logRun(){
        MyLog::info('Starting backup process '.$this->class.' with config',$this->config,'main');
    }
    function getConfig(){
        return $this-config;
    }
    function checkLocalExist(){
        $this->local->createDir($this->config['local']);    
    }  
    protected function startTime(){
        $this->time_start = microtime(true); 
    }
    protected function endTime(){
        $this->time_end = microtime(true);
        $this->execution_time = ($this->time_end - $this->time_start);
    }
    protected function logEnd(){
        $this->endTime();
        MyLog::info('Backup time execution for '.$this->class.' is '.$this->execution_time,$this->config,'main');
    }
    protected function rsync($src,$dstfolder,$exclude = array()){
        $exclude = empty($exclude) ? $this->config['exclude'] : $exclude;
        $this->dst->syncFiles($this->local,$src,$dstfolder,$exclude); 
    }  
    protected function archiveFiles($loc,$dst,$name,$increment = 0){
        ZipFolder::zip($this->local, $loc, $dst, $increment, $name);        
    }
    protected function increment($filename,$src,$local,$exclude){
        $time = 0;
        $type = 'm';
        if(date('j') != 1 || FULL_INCREMENT != 1){
            $time = Carbon::today()->subDay()->subSecond()->timestamp;   
            $type = 'd'; 
        } 
        $this->archiveFiles($src,$local,$filename.'-'.$type,$time);
        return $type;
    }
    protected function deleteOld($filename,$local,$time){ 
        DeleteOld::delete($this->local,$filename,$local,$time);
    }
    protected function mysqlDump($localhost, $user, $pass, $filename,$time,$local,$mysqlbase){
        try { 
            foreach($mysqlbase as $base){
                $files[] = '/'.trim($local,'/').'/'.$base.'.sql';
                $dump = new IMysqldump\Mysqldump('mysql:host='.$localhost.';dbname='.$base, $user, $pass,array('lock-tables' => false));
                $dump->start('/'.trim($local,'/').'/'.$base.'.sql');
            }
            ZipFiles::zip($filename,$local,$files); 
            foreach($files as $file){
                $this->local->delete($file);
            }
        } catch (\Exception $e) {
            echo '[DumpMySQL] ' . $e->getMessage();
        }
    }
    protected function mysqlAllBases($localhost, $user, $pass){
        $dbh = new \PDO( "mysql:host=$localhost", $user, $pass );
        $dbs = $dbh->query( 'SHOW DATABASES' );
        $return = array();
        while( ( $db = $dbs->fetchColumn( 0 ) ) !== false )
        {
            $return[] = $db;
        }
        return $return;
    }
}