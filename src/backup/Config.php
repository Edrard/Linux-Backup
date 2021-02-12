<?php

namespace backup;

class Config
{
    protected $config = [];
    protected $mailer = false;

    /**
    * put your comment there...
    *
    * @param string $file
    */
    public function __construct($file)
    {
        $this->config =  json_decode(file_get_contents($file), true);
        $this->fixBaseDirectory();
        $this->exclude();
        $this->filename();
        $this->mysql();
        $this->multiSrc();
    }
    /**
    * put your comment there...
    *
    */
    public function multiSrc()
    {
        foreach ($this->config['backup'] as $key => $back) {
            if ($back['src']) {
                $this->multiSrcToArray($key, $back['src']);
            }
        }
    }
    /**
    * put your comment there...
    *
    * @param string $key
    * @param string $src
    */
    protected function multiSrcToArray($key, $src)
    {
        $this->config['backup'][$key]['src'] = explode(',', trim($src, ','));
        if (! is_array($this->config['backup'][$key]['src']) || ($this->config['backup'][$key]['src']) === []) {
            $this->config['backup'][$key]['src'] = [];
            $this->config['backup'][$key]['src'][] =  $src;
        }
    }
    /**
    * put your comment there...
    *
    * @param string $name
    */
    public function get($name)
    {
        return isset($this->config[$name]) ? $this->config[$name] : false;
    }
    public function setIncrementStart($value)
    {
        foreach($this->config['backup'] as $key => $val){
            if($val['type'] == 'increment'){
                $this->config['backup'][$key]['full_backup_date'] = $value;
            }
        }
    }
    /**
    * put your comment there...
    *
    */
    protected function fixBaseDirectory()
    {
        foreach ($this->config['backup'] as $key => $back) {
            $this->config['backup'][$key]['local'] = $back['local'][0] === '/' ? $back['local'] : LOCAL_MAIN_DIR.'/'.$back['local'];
        }
    }
    /**
    * put your comment there...
    *
    */
    protected function filename()
    {
        foreach ($this->config['backup'] as $key => $back) {
            if ($back['filename'] && $back['fileinc']) {
                $this->config['backup'][$key]['true_filename'] = $back['filename'];
                $this->config['backup'][$key]['filename'] .= '-'.date($back['fileinc']);
            }
        }
    }
    /**
    * put your comment there...
    *
    */
    protected function exclude()
    {
        foreach ($this->config['backup'] as $key => $back) {
            $final = $this->_excludeFinal($back);
            $this->config['backup'][$key]['exclude'] = $final;
        }
    }
    /**
    * put your comment there...
    *
    * @param array $back
    */
    protected function _excludeFinal(array $back)
    {
        $final = [];
        if (trim($back['exclude'])) {
            foreach (explode(' ', $back['exclude']) as $exclude) {
                $final[] = trim($exclude, '/');
            }
        }
        return $final;
    }
    /**
    * put your comment there...
    *
    */
    protected function mysql()
    {
        foreach ($this->config['backup'] as $key => $back) {
            $final = [];
            if (trim($back['mysqlbase'])) {
                $final = explode(' ', trim($back['mysqlbase']));
            }
            $this->config['backup'][$key]['mysqlbase'] = $final;
        }
    }
    /**
    * put your comment there...
    *
    */
    public function returnActions()
    {
        return $this->config['backup'];
    }
    /**
    * put your comment there...
    *
    * @param string $id
    */
    public function returnConfig($id)
    {
        return $id && isset($this->config['config'][$id]) ? $this->config['config'][$id] : [];
    }
    /**
    * put your comment there...
    *
    * @param string $id
    */
    public function returnMysqlConfig($id)
    {
        return $id && isset($this->config['mysql'][$id]) ? $this->config['mysql'][$id] : [];
    }
    /**
    * put your comment there...
    *
    */
    public function returnLog()
    {
        return $this->config['log'];
    }
}
