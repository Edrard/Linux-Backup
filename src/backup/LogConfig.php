<?php
namespace backup;

use edrard\Log\MyLog;

Class LogConfig 
{
    protected static $config = array();

    public static function init($config){
        static::$config = $config;
        foreach(static::$config as $key => $conf){
            $func = $key.'LogSet';
            static::{$func}($conf);
        }
        die;
    }
    public static function fileLogSet($config){
        MyLog::init($config['dst'],'file');
        if($config['full'] != 1){
            MyLog::changeType(array('warning','error','critical'),'file');  
        } 
        static::$config['types'][] = 'file'; 
    }
}