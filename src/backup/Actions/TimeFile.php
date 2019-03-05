<?php
namespace backup\Actions;

use backup\Actions\IntProcess;

Class TimeFile extends AbsAction implements IntProcess
{  
    function run(){
        $this->logRun();
        foreach($this->config['src'] as $src){
            $this->archiveFiles($src,$this->config['local'],$this->config['filename']);
            $this->deleteOld($this->config['filename'],$this->config['local'],$this->config['days']);       
            $this->rsync($this->config['local'], $this->config['dstfolder']);
        }
        $this->logEnd();
    }  
}