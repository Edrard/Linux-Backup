<?php

namespace backup\Actions;

class NowFile extends AbsAction implements IntProcess
{
    /**
    * put your comment there...
    *
    */
    public function run()
    {
        $this->logRun();
        foreach ($this->config['src'] as $src) {
            $this->rsync($src, $this->config['dstfolder'], [], count($this->config['src']) > 1 ? 1 : '');
        }
        $this->logEnd();
    }
}
