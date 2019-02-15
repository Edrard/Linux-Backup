<?php
namespace backup\Actions;

use backup\Actions\IntProcess;

Class TimeFile extends AbsAction implements IntProcess
{  
    function run(){
        $this->logRun();
        $this->archiveFiles($this->config['src'],$this->config['local'],$this->config['filename']);
        $this->deleteOld($this->config['filename'],$this->config['local'],$this->config['days']);       
        $this->rsync($this->config['local'], $this->config['dstfolder']);
        $this->logEnd();
    }  
}