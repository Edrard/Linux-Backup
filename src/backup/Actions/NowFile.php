<?php
namespace backup\Actions;

use backup\Actions\IntProcess;

Class NowFile extends AbsAction implements IntProcess
{
    function run(){
        $this->logRun();
        $this->rsync($this->config['src'], $this->config['dstfolder']);
        $this->logEnd();
    }  
}