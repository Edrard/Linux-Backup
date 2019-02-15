<?php
namespace Flysystem;

use League\Flysystem\FilesystemInterface;
use League\Flysystem\PluginInterface;
use League\Flysystem\Filesystem;
use edrard\Log\MyLog;
use Emgag\Flysystem\Hash\HashPlugin;

class GenerateMd5
{
    static function generate(Filesystem $source, $path = null, $recursive = TRUE){
        $return = $source->listContents($path, $recursive);
        $source->addPlugin(new HashPlugin);
        foreach($return as $key => $val){
            if($val['type'] != 'dir'){
                $return[$key]['md5'] = $source->hash($val['dirname'].'/'.$val['basename'], 'md5');
            }
        }
        return $return;
    }
}