<?php
namespace backup\Manipulation;

use League\Flysystem\Filesystem;
use Carbon\Carbon;
use edrard\Log\MyLog;

Class DeleteOld
{ 
    public static function delete(Filesystem $file,$filename,$local,$days){
        $contents = $file->listContents($local, false);
        $time = Carbon::now()->subDays($days)->subSecond()->timestamp;
        foreach($contents as $con){
            if($time > $con['timestamp'] && preg_match("/".$filename."/iu", $con['filename'])){
                if($response = $file->delete($con['path'])){
                    MyLog::info("[Delete Old] File Deleted",array($con['path']),'main');
                }else{
                    MyLog::error("[Delete Old] Can`t Delete File:",array($con['path']),'main');
                }     
            }
        }
    }  
}