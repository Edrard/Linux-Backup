<?php

namespace backup\Actions;

class TimeFile extends AbsAction implements IntProcess
{
    /**
    * put your comment there...
    *
    */
    public function run()
    {
        $this->logRun();
        foreach ($this->config['src'] as $src) {
            $this->archiveFiles($src, $this->config['local'], $this->config['filename']);
            $this->deleteOld($this->config['filename'], $this->config['local'], $this->config['days']);
            $this->rsync($this->config['local'], $this->config['dstfolder']);
        }
        $this->logEnd();
    }
}
