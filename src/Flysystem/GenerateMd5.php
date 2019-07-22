<?php

namespace Flysystem;

use Emgag\Flysystem\Hash\HashPlugin;
use League\Flysystem\Filesystem;

class GenerateMd5
{
    /**
    * put your comment there...
    *
    * @param Filesystem $source
    * @param string $path
    * @param bool $recursive
    */
    public static function generate(Filesystem $source, $path = null, $recursive = true)
    {
        $return = $source->listContents($path, $recursive);
        $source->addPlugin(new HashPlugin());
        foreach ($return as $key => $val) {
            if ($val['type'] != 'dir') {
                $return[$key]['md5'] = $source->hash($val['dirname'].'/'.$val['basename'], 'md5');
            }
        }
        return $return;
    }
}
