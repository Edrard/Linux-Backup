<?php
namespace backup\Actions;

use backup\Actions\IntProcess;

Class NowFile extends AbsAction implements IntProcess
{
    function run(){
        $this->logRun();
        foreach($this->config['src'] as $src){
            $this->rsync($src, $this->config['dstfolder'],array(),count($this->config['src']) > 1 ? 1 : '');
        }
        $this->logEnd();
    }  
}