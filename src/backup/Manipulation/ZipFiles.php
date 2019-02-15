<?php
namespace backup\Manipulation;

use League\Flysystem\FilesystemInterface;
use League\Flysystem\Filesystem;
use edrard\Log\MyLog;
use Exc\Base;
use Carbon\Carbon;
use \ZipArchive;

class ZipFiles
{
    protected static $where;
    protected static $path;
    protected static $name;
    protected static $zipper;

    public static function zip($name,$where,array $path)
    {
        static::$name = $name; 
        static::$where = trim($where,'/');
        static::$path = $path;
        static::$zipper =  new ZipArchive();
        static::$zipper->open('/'.static::$where.'/'.static::$name.'.zip', ZIPARCHIVE::CREATE);
        //dd( static::$src_path, static::$where,static::$increment,static::$name,static::$filesystem);
        try{
            if(!$name){ 
                throw new Base('No name setted','error');
            }
            static::addFiles();
        }Catch(Base $e){
            MyLog::error('[ZipFiles] '.$e->getMessage()); 
            die($e->getMessage());   
        }
    }
    protected static function addFiles(){
        foreach(static::$path as $path){ 
            static::$zipper->addFile($path,pathinfo($path)['basename']);
            MyLog::info("Added file to archive ".static::$name.'.zip',array($path),'main');
        }
        static::$zipper->close();
        MyLog::info("Files zipped",array(),'main');    
    }
}