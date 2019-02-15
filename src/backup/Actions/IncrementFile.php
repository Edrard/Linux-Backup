<?php
namespace backup\Actions;

use Carbon\Carbon;
use backup\Actions\IntProcess;

Class IncrementFile extends AbsAction implements IntProcess
{
    function run(){            
        $this->logRun();
        $type = $this->increment($this->config['filename'],$this->config['src'], $this->config['local'],$this->config['exclude']);
        if($type == 'm'){
            $subm = Carbon::now()->subMonths(max(0,$this->config['months']));
            if($this->config['months'] > 0){
                $subm->subDay();
            }  
            $time = Carbon::now()->diffInDays($subm);
            $this->deleteOld($this->config['true_filename'],$this->config['local'],$time);
        }
        $this->rsync($this->config['local'], $this->config['dstfolder']);
        $this->logEnd();
    }  
}