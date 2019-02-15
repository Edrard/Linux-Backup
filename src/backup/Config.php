<?php
namespace backup;

Class Config 
{
    protected $config = array();

    function __construct($file){
        $this->config =  json_decode(file_get_contents($file),TRUE);
        $this->fixBaseDirectory();
        $this->exclude();
        $this->filename();
        $this->mysql();
    }
    function get($name){
        return isset($this->config[$name]) ? $this->config[$name] : FALSE;    
    }
    protected function fixBaseDirectory(){
        foreach($this->config['backup'] as $key => $back){
            $this->config['backup'][$key]['local'] = $back['local'][0] == '/' ? $back['local'] : LOCAL_MAIN_DIR.'/'.$back['local'];
        }
    }
    protected function filename(){
        foreach($this->config['backup'] as $key => $back){
            if($back['filename'] && $back['fileinc']){
                $this->config['backup'][$key]['true_filename'] = $back['filename'];
                $this->config['backup'][$key]['filename'] .= '-'.date($back['fileinc']); 
            }   
        }
    }
    protected function exclude(){
        foreach($this->config['backup'] as $key => $back){
            $final = array();
            if(trim($back['exclude'])){
                foreach(explode(' ', $back['exclude']) as $ex){
                    $final[] = trim($ex,'/');  
                }    
            }
            $this->config['backup'][$key]['exclude'] = $final;
        }
    }
    protected function mysql(){
        foreach($this->config['backup'] as $key => $back){
            $final = array();
            if(trim($back['mysqlbase'])){
                $final = explode(' ', trim($back['mysqlbase']));
            }
            $this->config['backup'][$key]['mysqlbase'] = $final;
        }
    }
    function returnActions(){
        return $this->config['backup'];
    }
    function returnConfig($id){
        return $id && isset($this->config['config'][$id]) ? $this->config['config'][$id] : array();
    }
    function returnMysqlConfig($id){
        return $id && isset($this->config['mysql'][$id]) ? $this->config['mysql'][$id] : array();
    }
    function returnLog(){
        return $this->config['log'];
    }
}