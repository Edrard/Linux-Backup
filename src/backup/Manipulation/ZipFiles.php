<?php

namespace backup\Manipulation;

use edrard\Log\MyLog;
use Exc\Base;

class ZipFiles
{
    protected static $where;
    protected static $path;
    protected static $name;
    protected static $zipper;

    /**
    * put your comment there...
    *
    * @param string $name
    * @param string $where
    * @param array $path
    */
    public static function zip($name, $where, array $path)
    {
        static::$name = $name;
        static::$where = trim($where, '/');
        static::$path = $path;
        static::$zipper = '/'.static::$where.'/'.static::$name.'.zip';
        //dd( static::$src_path, static::$where,static::$increment,static::$name,static::$filesystem);
        try {
            if (! $name) {
                throw new Base('No name setted', 'error');
            }
            static::addFiles();
        } catch (Base $error) {
            MyLog::error('[ZipFiles] '.$error->getMessage());
            die($error->getMessage());
        }
    }
    /**
    * put your comment there...
    *
    */
    protected static function addFiles()
    {
        foreach (static::$path as $path) {
            $relativePath = pathinfo($path)['basename'];
            $change_dir = str_replace($relativePath, '', $path);
            if (file_exists(static::$zipper)) {
                exec('cd '.$change_dir.' && zip -u '.static::$zipper.' "'.$relativePath.'"');
            } else {
                exec('cd '.$change_dir.' && zip -9 '.static::$zipper.' "'.$relativePath.'"');
            }
            MyLog::info("Added file to archive ".static::$name.'.zip', [$path], 'main');
        }
        MyLog::info("Files zipped", [], 'main');
    }
}
