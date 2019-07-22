<?php

namespace backup\Actions;

class TimeMysql extends AbsAction implements IntProcess
{
    /**
    * put your comment there...
    *
    */
    public function run()
    {
        $this->logRun();
        if (in_array('+', $this->config['mysqlbase'])) {
            $this->config['mysqlbase'] = $this->mysqlAllBases($this->mysql['host'], $this->mysql['user'], $this->mysql['pass']);
        }
        $this->mysqlDump($this->mysql['host'], $this->mysql['user'], $this->mysql['pass'], $this->config['filename'], $this->config['local'], $this->config['mysqlbase']);
        $this->deleteOld($this->config['true_filename'], $this->config['local'], $this->config['days']);
        $this->rsync($this->config['local'], $this->config['dstfolder']);
        $this->logEnd();
    }
}
