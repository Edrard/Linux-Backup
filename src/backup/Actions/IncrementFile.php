<?php

namespace backup\Actions;

use Carbon\Carbon;

class IncrementFile extends AbsAction implements IntProcess
{
    /**
    * put your comment there...
    *
    */
    public function run()
    {
        $this->logRun();
        foreach ($this->config['src'] as $src) {
            $type = $this->increment($this->config['filename'], $src, $this->config['local'], $this->config['exclude']);
            $this->monthClean($type);
            $this->rsync($this->config['local'], $this->config['dstfolder']);
            $this->logEnd();
        }
    }
    /**
    * put your comment there...
    *
    * @param string $type
    */
    protected function monthClean($type)
    {
        if ($type == 'm') {
            $subm = Carbon::now()->subMonths(max(0, $this->config['months']));
            if ($this->config['months'] > 0) {
                $subm->subDay();
            }
            $time = Carbon::now()->diffInDays($subm);
            $this->deleteOld($this->config['true_filename'], $this->config['local'], $time);
        }
    }
}
