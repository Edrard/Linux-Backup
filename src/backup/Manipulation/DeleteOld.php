<?php

namespace backup\Manipulation;

use Carbon\Carbon;
use edrard\Log\MyLog;
use League\Flysystem\Filesystem;

class DeleteOld
{
    /**
    * put your comment there...
    *
    * @param Filesystem $file
    * @param string $filename
    * @param string $local
    * @param int $days
    */
    public static function delete(Filesystem $file, $filename, $local, $days)
    {
        $contents = $file->listContents($local, false);
        $time = Carbon::now()->subDays($days)->subSecond()->timestamp;
        foreach ($contents as $con) {
            if ($time > $con['timestamp'] && preg_match("/".$filename."/iu", $con['filename'])) {
                static::logDeletion($file->delete($con['path']), $con['path']);
            }
        }
    }
    /**
    * put your comment there...
    *
    * @param bool $status
    * @param string $path
    */
    protected static function logDeletion($status, $path)
    {
        if ($status) {
            MyLog::info("[Delete Old] File Deleted", [$path], 'main');
        } else {
            MyLog::error("[Delete Old] Can`t Delete File:", [$path], 'main');
        }
    }
}
