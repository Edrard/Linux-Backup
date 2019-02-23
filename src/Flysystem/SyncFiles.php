<?php
namespace Flysystem;

use League\Flysystem\FilesystemInterface;
use League\Flysystem\PluginInterface;
use League\Flysystem\Filesystem;
use edrard\Log\MyLog;

class SyncFiles implements PluginInterface
{
    protected $filesystem;
    protected $local;
    protected $src_path;
    protected $dst_path;
    protected $contents = array();
    protected $dst = array();
    protected $dir_create = array();
    protected $dir_delete = array();
    protected $file_create = array();
    protected $file_delete = array();
    protected $exclude = array();

    public function setFilesystem(FilesystemInterface $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    public function getMethod()
    {
        return 'syncFiles';
    }

    public function handle(Filesystem $src, $src_path, $path = null, array $exclude = array(), $recursive = TRUE)
    {
        $this->local = $src;
        $this->src_path = $src_path;
        $this->dst_path = $path === NULL ? '' : $path;
        $this->exclude = $exclude;    
        try{
            $this->contents = $this->local->listContents($this->src_path, $recursive);
            $this->dst = $this->filesystem->listContents($this->dst_path, $recursive);
            MyLog::info("[Sync] Check if exist",array(),'main');
            foreach($this->contents as $key => $way){
                $this->contents[$key]['path'] = $way['path'] = preg_replace('#^'.trim($this->src_path,'/').'#iU', '', $way['path']);

                //Check if exist 
                $this->checkIfExist($way);            
            }
            MyLog::info("[Sync] Check if delete",array(),'main');
            foreach($this->dst as $key => $out){
                //Check if delete 
                $this->checkIfDelete($out);            
            }
            MyLog::info("[Sync] Files deleting process starting",array(),'main');
            $this->deleteFiles();
            MyLog::info("[Sync] Directory deleting process starting",array(),'main');
            $this->deleteDirectory();
            MyLog::info("[Sync] Directory creating process starting",array(),'main');
            $this->createDirectory();
            MyLog::info("[Sync] Files copy process starting",array(),'main');
            $this->CopyUpdateFiles();                            
        }Catch(\Exception $e){
            MyLog::error('[SyncFiles] '.$e->getMessage()); 
            die($e->getMessage());   
        }
        $this->resset();
    }
    protected function checkIfExist($way){ 
        foreach($this->dst as $key => $d){  
            $this->dst[$key]['path'] = $d['path'] = preg_replace('#^'.trim($this->dst_path,'/').'#iU', '', $d['path']); 
            if($way['type'] == $d['type'] && $way['path'] == $d['path']){
                if($way['type'] == 'dir'){
                    return;
                }elseif(isset($out['basename']) && isset($d['basename']) && $out['basename'] == $d['basename']){
                    if(isset($out['size']) && isset($d['size'])){
                        if($out['size'] == $d['size']){
                            return;  
                        }
                    }else{
                        return;
                    }
                }
            }        
        }
        $cr = $way['type'].'_create';
        $this->{$cr}[] = $way;
    }
    protected function checkIfDelete($out){
        foreach($this->contents as $key => $d){
            if($out['type'] == $d['type'] && $out['path'] == $d['path']){
                if($out['type'] == 'dir'){
                    return;
                }elseif(isset($out['basename']) && isset($d['basename']) && $out['basename'] == $d['basename']){
                    if(isset($out['size']) && isset($d['size'])){
                        if($out['size'] == $d['size']){
                            return;  
                        }
                    }else{
                        return;
                    }
                }
            }        
        } 
        $cr = $out['type'].'_delete';
        $this->{$cr}[] = $out;   
    }
    protected function deleteDirectory(){
        foreach($this->dir_delete as $del){
            $dir = '/'.trim($this->dst_path,'/').$del['path'];
            if($response = $this->filesystem->deleteDir($dir)){
                MyLog::info("[Sync] Folder Deleted",array($dir),'main');
            }else{
                MyLog::error("[Sync] Can`t Delete Folder:",array($dir),'main');
            }   

        } 
    }
    protected function deleteFiles(){ 
        foreach($this->file_delete as $del){
            $file = '/'.trim($this->dst_path,'/').$del['path'];
            if($response = $this->filesystem->delete($file)){
                MyLog::info("[Sync] File Deleted",array($file),'main');
            }else{
                MyLog::error("[Sync] Can`t Delete File:",array($file),'main');
            }   

        } 
    }
    protected function createDirectory(){
        foreach($this->dir_create as $cr){ 
            $dir = '/'.trim($this->dst_path,'/').$cr['path'];
            if($response = $this->filesystem->createDir($dir)){
                MyLog::info("[Sync] Folder created",array($dir),'main');
            }else{
                MyLog::error("[Sync] Can`t create folder:",array($dir),'main');
            }   
        } 
    }
    protected function CopyUpdateFiles(){
        // $this->file_create
        foreach($this->file_create as $cr){
            $file = '/'.trim($this->dst_path,'/').$cr['path'];
            $local = '/'.trim($this->src_path,'/').$cr['path'];
            $read = $this->local->readStream($local);
            if($response = $this->filesystem->putStream($file,$read)){
                MyLog::info("[Sync] File update/created",array($file),'main');
            }else{
                MyLog::error("[Sync] Can`t create/update file:",array($file),'main');
            }   
        } 
    }
    protected function resset(){
        $this->contents = array();
        $this->dst = array();
        $this->dir_create = array();
        $this->dir_delete = array();
        $this->file_create = array();
        $this->file_delete = array();
        $this->exclude = array();
    }
}