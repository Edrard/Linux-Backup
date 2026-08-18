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
        if (! is_readable($file)) {
            throw new \InvalidArgumentException('Config file is not readable: '.$file);
        }
        $this->config =  json_decode(file_get_contents($file), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException('Config JSON error: '.json_last_error_msg());
        }
        $this->normalize();
        $this->fixBaseDirectory();
        $this->exclude();
        $this->filename();
        $this->mysql();
        $this->multiSrc();
    }
    /**
    * Normalize optional config keys before derived values are built.
    */
    protected function normalize()
    {
        if (! is_array($this->config) || ! isset($this->config['backup']) || ! is_array($this->config['backup'])) {
            throw new \InvalidArgumentException('Config must contain backup section');
        }
        $this->config['config'] = isset($this->config['config']) && is_array($this->config['config']) ? $this->config['config'] : [];
        $this->config['mysql'] = isset($this->config['mysql']) && is_array($this->config['mysql']) ? $this->config['mysql'] : [];
        $this->config['log'] = isset($this->config['log']) && is_array($this->config['log']) ? $this->config['log'] : [];
        $this->config['log']['file'] = isset($this->config['log']['file']) && is_array($this->config['log']['file']) ? $this->config['log']['file'] : [];
        $this->config['log']['mail'] = isset($this->config['log']['mail']) && is_array($this->config['log']['mail']) ? $this->config['log']['mail'] : [];
        $this->config['log']['file'] = array_merge(['dst' => 'nlog', 'full' => ''], $this->config['log']['file']);
        $this->config['log']['mail'] = array_merge([
            'user' => '',
            'pass' => '',
            'smtp' => '',
            'port' => '25',
            'from' => '',
            'to' => '',
            'separate' => '',
            'hostname' => '',
        ], $this->config['log']['mail']);
        $defaults = [
            'src' => '',
            'dstfolder' => '',
            'local' => '',
            'type' => 'now',
            'days' => '0',
            'months' => '0',
            'full_backup_date' => '1',
            'filename' => '',
            'fileinc' => '',
            'typebackup' => 'file',
            'exclude' => '',
            'mysqlbase' => '',
            'mysqlbase_table_setup' => [],
            'mysqlconfig' => '',
            'dst' => '',
        ];
        foreach ($this->config['backup'] as $key => $back) {
            if (! is_array($back)) {
                throw new \InvalidArgumentException('Backup config '.$key.' must be an object');
            }
            $this->config['backup'][$key] = array_merge($defaults, $back);
        }
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
            } else {
                $this->config['backup'][$key]['src'] = [];
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
        $this->config['backup'][$key]['src'] = array_values(array_filter(array_map('trim', explode(',', trim($src, ','))), function ($path) {
            return $path !== '';
        }));
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
            if ($back['local'] === '') {
                $this->config['backup'][$key]['local'] = LOCAL_MAIN_DIR;
                continue;
            }
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
            if ($back['filename']) {
                $this->config['backup'][$key]['true_filename'] = $back['filename'];
            }
            if ($back['filename'] && $back['fileinc']) {
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
