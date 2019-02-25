<?php
namespace backup\Manipulation;

use League\Flysystem\FilesystemInterface;
use League\Flysystem\Filesystem;
use edrard\Log\MyLog;
use Exc\Base;
use Carbon\Carbon;

class ZipFolder
{
    protected static $filesystem;
    protected static $src_path;
    protected static $where;
    protected static $increment;
    protected static $name;
    protected static $zipper; 
    protected static $zip_in = 100;

    public static function zip(Filesystem $file, $src_path, $where = null, $increment = 0, $name, $recursive = TRUE)
    {

        static::$src_path = trim($src_path,'/');
        static::$where = $where === NULL ? '' : trim($where,'/');
        static::$increment = $increment;  
        static::$name = $name;
        static::$filesystem = $file;
        static::$zipper = '/'.static::$where.'/'.static::$name.'.zip'; 
        //dd('/'.static::$where.'/'.static::$name.'.zip');
        try{
            //dd( static::$src_path, static::$where,static::$increment,static::$name,static::$filesystem);
            if(!$name){ 
                throw new Base('No name setted','error');
            }
            static::zipRun();
        }Catch(Base $e){
            MyLog::error('[ZipFolder] '.$e->getMessage()); 
            die($e->getMessage());   
        }
    }
    protected static function zipRun(){
        $contents = static::$filesystem->listContents(static::$src_path,true);
        $i = 0;
        $list = array();
        $cd = '';
        foreach($contents as $con){ 
            if(($con['timestamp'] > static::$increment || static::$increment == 0) && $con['type'] != 'dir'){
                $relative_path = substr('/'.$con['path'], strlen('/'.static::$src_path) + 1);
                $cd = str_replace($relative_path,'','/'.$con['path']);   print_r($cd);
                $list[] = $relative_path;
                $i++;
                if($i >= static::$zip_in){
                    static::zipList($cd,$list);
                    $list = array();
                    $i = 0;    
                }
                MyLog::info("Added file to archive ".static::$name.'.zip',array('/'.$con['path']),'main');
            }   
        }
        static::zipList($cd,$list); 
        MyLog::info("Files zipped",array(),'main'); 
    }
    protected static function zipList($cd,$list){
        if(empty($list)){
            return;
        }        
        MyLog::info("Adding to zip files",array(),'main');
        $list = implode('" "',$list); 
        if(file_exists(static::$zipper)){
            exec('cd '.$cd.' && zip -u '.static::$zipper.' "'.$list.'"' );
        }else{ 
            exec('cd '.$cd.' && zip -9 '.static::$zipper.' "'.$list.'"' );
        }    
    }
}