<?php
namespace backup\Manipulation;

use League\Flysystem\FilesystemInterface;
use League\Flysystem\Filesystem;
use edrard\Log\MyLog;
use Exc\Base;
use Carbon\Carbon;

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
        static::$zipper = '/'.static::$where.'/'.static::$name.'.zip';
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
            $relativePath = pathinfo($path)['basename'];
            $cd = str_replace($relativePath,'',$path);
            if(file_exists(static::$zipper)){
                exec('cd '.$cd.' && zip -u '.static::$zipper.' "'.$relativePath.'"' );
            }else{ 
                exec('cd '.$cd.' && zip -9 '.static::$zipper.' "'.$relativePath.'"' );
            }
            MyLog::info("Added file to archive ".static::$name.'.zip',array($path),'main');
        }
        MyLog::info("Files zipped",array(),'main');    
    }
}