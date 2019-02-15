<?php

namespace Exc;

use Exception;
use edrard\Log\MyLog;

class Base extends Exception
{

    public function __construct($message)
    {
        $args = func_get_args();
        $message = $this->create($args);
        $code = isset($args[2]) ? (int) $args[2] : 0;
        parent::__construct($message,$code);
    }

    protected function create(array $args)
    {
        if(isset($args[1])){
            MyLog::{$args[1]}('['.get_class($this).'] '.$args[0]);    
        }
        return $args[0];
    }
}
