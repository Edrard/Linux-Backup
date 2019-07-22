<?php

namespace backup\Manipulation;

use edrard\Log\MyLog;
use Exc\Base;
use League\Flysystem\Filesystem;

class ZipFolder
{
    protected static $filesystem;
    protected static $src_path;
    protected static $where;
    protected static $increment;
    protected static $name;
    protected static $zipper;
    protected static $zip_in = 100;

    /**
    * put your comment there...
    *
    * @param Filesystem $file
    * @param string $src_path Source path
    * @param string $where Local path
    * @param string $increment Increment time
    * @param string $name file name
    */
    public static function zip(
        Filesystem $file,
        $src_path,
        $where = null,
        $increment,
        $name
    ) {
        static::$src_path = trim($src_path, '/');
        static::$where = $where === null ? '' : trim($where, '/');
        static::$increment = $increment;
        static::$name = $name;
        static::$filesystem = $file;
        static::$zipper = '/'.static::$where.'/'.static::$name.'.zip';
        //dd('/'.static::$where.'/'.static::$name.'.zip');
        try {
            //dd( static::$src_path, static::$where,static::$increment,static::$name,static::$filesystem);
            if (! $name) {
                throw new Base('No name setted', 'error');
            }
            static::zipRun();
        } catch (Base $error) {
            MyLog::error('[ZipFolder] '.$error->getMessage());
            die($error->getMessage());
        }
    }
    /**
    * put your comment there...
    *
    */
    protected static function zipRun()
    {
        static::$increment == 0 ?
        static::nonIncrementZip() :
        static::incrementZip();

        MyLog::info("Files zipped", [], 'main');
    }
    /**
    * put your comment there...
    *
    */
    protected static function incrementZip()
    {
        $contents = static::$filesystem->listContents(static::$src_path, true);
        $i = 0;
        $list = [];
        $change_dir = '';
        foreach ($contents as $con) {
            if ($con['timestamp'] > static::$increment && $con['type'] != 'dir') {
                $relative_path = substr('/'.$con['path'], strlen('/'.static::$src_path) + 1);
                $change_dir = str_replace($relative_path, '', '/'.$con['path']);
                $list[] = $relative_path;
                $i++;
                static::ressetCounter($i, $change_dir, $list);
                MyLog::info(
                    "Added file to archive ".static::$name.'.zip',
                    ['/'.$con['path']],
                    'main'
                );
            }
        }
        static::zipList($change_dir, $list);
    }
    /**
    * put your comment there...
    *
    * @param int $i
    * @param string $change_dir
    * @param array $list List of files
    * @return void
    */
    protected static function ressetCounter(int &$i, string $change_dir, array &$list)
    {
        if ($i >= static::$zip_in) {
            static::zipList($change_dir, $list);
            $list = [];
            $i = 0;
        }
    }
    /**
    * put your comment there...
    *
    */
    protected static function nonIncrementZip()
    {
        $change_dir = explode('/', static::$src_path);
        $relative_path = array_pop($change_dir);
        $change_dir = '/'.implode('/', $change_dir);
        static::zipFolder($change_dir, $relative_path);
        MyLog::info("Added folder to archive ".static::$name.'.zip', [static::$src_path], 'main');
    }
    /**
    * put your comment there...
    *
    * @param string $change_dir
    * @param string $folder
    * @return void
    */
    protected static function zipFolder($change_dir, $folder)
    {
        if (! $folder && ! $change_dir) {
            return;
        }

        MyLog::info("Adding to zip folder: ".$folder, [$change_dir], 'main');
        exec('cd '.$change_dir.' && zip -9 -r '.static::$zipper.' "'.$folder.'"');
    }
    /**
    * put your comment there...
    *
    * @param string $change_dir
    * @param string $list
    *
    */
    protected static function zipList($change_dir, array $list)
    {
        if ($list === []) {
            return;
        }
        MyLog::info("Adding to zip files", [], 'main');
        $list = implode('" "', $list);
        if (file_exists(static::$zipper)) {
            exec('cd '.$change_dir.' && zip -u '.static::$zipper.' "'.$list.'"');
        } else {
            exec('cd '.$change_dir.' && zip -9 '.static::$zipper.' "'.$list.'"');
        }
    }
}
