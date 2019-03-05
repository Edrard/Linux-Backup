<?php
namespace backup;

use edrard\Log\MyLog;

Class Config 
{
    protected $config = array();
    protected $mailer = FALSE;

    function __construct($file){
        $this->config =  json_decode(file_get_contents($file),TRUE);         
        $this->fixBaseDirectory();
        $this->exclude();
        $this->filename();
        $this->mysql();
        $this->initLog();
        $this->multiSrc();
    }
    function multiSrc(){
        foreach($this->config['backup'] as $key => $back){
            if($back['src']){
                $this->config['backup'][$key]['src'] = explode(',',trim($back['src'],',')); 
                if(!is_array($this->config['backup'][$key]['src']) || empty($this->config['backup'][$key]['src'])){
                    $this->config['backup'][$key]['src'] = array();
                    $this->config['backup'][$key]['src'][] =  $back['src'];
                }
            }   
        }   
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
    protected function initLog(){
        $this->fileLogSet();
        $this->mailLogSet();
    }
    protected function fileLogSet(){  
        MyLog::init($this->config['log']['file']['dst'],'main');
        if($this->config['log']['file']['full'] != 1){
            MyLog::changeType(array('warning','error','critical'),'main');  
        } 
    }
    protected function mailLogSet(){

        $mail = $this->config['log']['mail'];
        if($mail['user'] && $mail['pass'] && $mail['smtp']){
            $transport = (new \Swift_SmtpTransport($mail['smtp'], $mail['port']))
            ->setUsername($mail['user'])
            ->setPassword($mail['pass'])
            ;
            $this->mailer = new \Swift_Mailer($transport);

            register_shutdown_function(array($this, 'mailSend'));
        }

    }
    public function mailSend(){
        if($this->mailer !== FALSE){
            $config = MyLog::getLogConfig('main');  
            $log_files = glob($config['path'].'/'.'*'.$config['date'].'*');
            $read = '';
            foreach($log_files as $log){
                $type = $this->getLogType($log,$config);
                $read .= $type."\n\n".file_get_contents($log);
                if($this->config['log']['mail']['separate'] == 1){
                    $this->sendMailLog($read,'['.$type.' '.$this->config['log']['mail']['hostname'].']');
                    $read = '';
                }        
            }
            if($this->config['log']['mail']['separate'] != 1){
                $this->sendMailLog($read,'['.$this->config['log']['mail']['hostname'].']');
            }  
        }   
    }
    protected function getLogType($log,$config){
        $log =  pathinfo($log);
        $filename = $log['filename'];
        $return = '';
        foreach($config['types'] as $type => $file){
            $logname = pathinfo($file);
            $logname = $logname['filename'];    
            if(strpos($filename,$logname) !== FALSE){
                $return .= ' '.$type;
            }
        }
        return $return ? trim($return) : 'unknown';
    }
    protected function sendMailLog($text,$subject){
        $to = explode(',',$this->config['log']['mail']['to']);
        $message = (new \Swift_Message($subject))
        ->setFrom([$this->config['log']['mail']['from'] => $this->config['log']['mail']['hostname']])
        ->setTo($to)
        ->setBody($text)
        ;
        $result = $this->mailer->send($message);  
    }
}