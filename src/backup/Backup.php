<?php
namespace backup;

use \backup\Config;
use League\Flysystem\Filesystem;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Adapter\Ftp;
use \Flysystem\SyncFiles;
use \Flysystem\ZipFolder;
use Exc\NoDistinationException;
use Exc\NoInicializationException;
use Emgag\Flysystem\Hash\HashPlugin;
use edrard\Log\MyLog;

Class Backup 
{   
    protected $config;
    protected $run;
    protected $initial = FALSE;
    protected $dst = array();
    function __construct(Config $config){
        $this->config =  $config; 
    } 
    protected function initial(){
        $adapter = new Local('/');
        $local = new Filesystem($adapter);
        foreach($this->config->returnActions() as $key => $elem){ 
            $ctype = (strtolower($elem['typebackup']) != 'mysql' ? ucfirst(strtolower($elem['type'])) : 'Time').ucfirst(strtolower($elem['typebackup']));
            $dst = $this->distination($this->config->returnConfig($elem['dst']),$key,$elem['dst']);
            $class = '\backup\Actions\\'.$ctype;
            $this->run[] = new $class($elem,$dst,$local,$this->config->returnMysqlConfig($elem['mysqlconfig']));
            MyLog::info('Initialization backup process - '.$ctype.' with config',$elem,'main');
            $this->initial = $this->initial === FALSE ? TRUE : TRUE;    
        } 
    } 
    public function run(){
        $this->initial();
        $this->startBackup();   
    }
    protected function startBackup(){
        try{
            if($this->initial !== FALSE){
                MyLog::info("Starting Backup process",array(),'main');
                foreach($this->run as $class){
                    $class->run();
                }
                return;
            } 
            throw new NoInicializationException('No Inicialization maked','error');
        }Catch(NoInicializationException $e){
            die('[NoInicializationException] '.$e->getMessage());
        }  
    }
    protected function distination(array $config = array(),$key, $id){ 
        if(!isset($this->dst[$config['type']][$id]) || !$this->dst[$config['type']][$id]){
            try{
                if(empty($config)){
                    throw new NoDistinationException('No destination for element with key = '.$key,'error');  
                }  
                $function = $config['type'].'Adapter';
                $filesystem = new Filesystem($this->{$function}($config));
                MyLog::info('Setted config',$config,'main');
                $filesystem->addPlugin(new SyncFiles);
                $this->dst[$config['type']][$id] = $filesystem;
            }Catch(NoDistinationException $e){
                die('[NoDistinationException] '.$e->getMessage());
            }   
        } 
        return $this->dst[$config['type']][$id];
    }
    protected function ftpAdapter($config){
        return new Ftp ([
            'host' => $config['host'],
            'username' => $config['user'],
            'password' => $config['pass'],

            /** optional config settings */
            'passive' => true,
        ]);       
    }
}
