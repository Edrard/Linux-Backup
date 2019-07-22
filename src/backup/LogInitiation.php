<?php

namespace backup;

use edrard\Log\MyLog;

class LogInitiation
{
    protected $config = [];
    protected $mailer = false;

    /**
    * put your comment there...
    *
    * @param Config $config
    */
    public function __construct(Config $config)
    {
        $this->config =  $config->returnLog();
        $this->initLog();
    }
    /**
    * put your comment there...
    *
    */
    protected function initLog()
    {
        $this->fileLogSet();
        $this->mailLogSet();
    }
    /**
    * put your comment there...
    */
    protected function fileLogSet()
    {
        MyLog::init($this->config['file']['dst'], 'main');
        if ($this->config['file']['full'] != 1) {
            MyLog::changeType(['warning','error','critical'], 'main');
        }
    }
    /**
    * put your comment there...
    *
    */
    protected function mailLogSet()
    {
        $mail = $this->config['mail'];
        if ($mail['user'] && $mail['pass'] && $mail['smtp']) {
            $transport = (new \Swift_SmtpTransport($mail['smtp'], $mail['port']))
                ->setUsername($mail['user'])
                ->setPassword($mail['pass'])
            ;
            $this->mailer = new \Swift_Mailer($transport);

            register_shutdown_function([$this, 'mailSend']);
        }
    }
    /**
    * put your comment there...
    *
    */
    public function mailSend()
    {
        if ($this->mailer !== false) {
            return;
        }
        $config = MyLog::getLogConfig('main');
        $log_files = glob($config['path'].'/*'.$config['date'].'*');
        $read = '';
        foreach ($log_files as $log) {
            $type = $this->getLogType($log, $config);
            $read .= $type."\n\n".file_get_contents($log);
            if ($this->config['mail']['separate'] == 1) {
                $this->sendMailLog($read, '['.$type.' '.$this->config['mail']['hostname'].']');
                $read = '';
            }
        }
        if ($this->config['mail']['separate'] != 1) {
            $this->sendMailLog($read, '['.$this->config['mail']['hostname'].']');
        }
    }
    /**
    * put your comment there...
    *
    * @param string $log
    * @param array $config
    */
    protected function getLogType($log, array $config)
    {
        $log =  pathinfo($log);
        $filename = $log['filename'];
        $return = '';
        foreach ($config['types'] as $type => $file) {
            $logname = pathinfo($file);
            $logname = $logname['filename'];
            if (strpos($filename, $logname) !== false) {
                $return .= ' '.$type;
            }
        }
        return $return ? trim($return) : 'unknown';
    }
    /**
    * put your comment there...
    *
    * @param string $text
    * @param string $subject
    */
    protected function sendMailLog($text, $subject)
    {
        $to = explode(',', $this->config['mail']['to']);
        $message = (new \Swift_Message($subject))
            ->setFrom([$this->config['mail']['from'] => $this->config['mail']['hostname']])
            ->setTo($to)
            ->setBody($text)
        ;
        return $this->mailer->send($message);
    }
}