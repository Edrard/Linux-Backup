<?php

namespace backup;

use edrard\Log\MyLog;
use Exc\NoDistinationException;
use Exc\NoInicializationException;
use Flysystem\SyncFiles;
use League\Flysystem\Adapter\Ftp;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;

class Backup
{
    protected $config;
    protected $run;
    protected $initial = false;
    protected $dst = [];
    /**
    * put your comment there...
    *
    * @param Config $config
    */
    public function __construct(Config $config)
    {
        $this->config =  $config;
    }
    /**
    * put your comment there...
    *
    */
    protected function initial()
    {
        $adapter = new Local('/', LOCK_SH, Local::SKIP_LINKS);
        $local = new Filesystem($adapter);
        foreach ($this->config->returnActions() as $key => $elem) {
            $ctype = (strtolower($elem['typebackup']) !== 'mysql' ? ucfirst(strtolower($elem['type'])) : 'Time').ucfirst(strtolower($elem['typebackup']));
            $dst = $this->distination($this->config->returnConfig($elem['dst']), $key, $elem['dst']);
            $class = '\backup\Actions\\'.$ctype;
            $this->run[] = new $class($elem, $dst, $local, $this->config->returnMysqlConfig($elem['mysqlconfig']));
            MyLog::info('Initialization backup process - '.$ctype.' with config', $elem, 'main');
            $this->initial = $this->initial === false ? true : true;
        }
    }
    /**
    * put your comment there...
    *
    */
    public function run()
    {
        try {
            $this->initial();
            $this->startBackup();
        } catch (NoInicializationException $error) {
            die('[NoInicializationException] '.$error->getMessage());
        } catch (NoDistinationException $error) {
            die('[NoDistinationException] '.$error->getMessage());
        }
    }
    /**
    * put your comment there...
    *
    */
    protected function startBackup()
    {
        if ($this->initial !== false) {
            MyLog::info("Starting Backup process", [], 'main');
            foreach ($this->run as $class) {
                $class->run();
            }
            return;
        }
        throw new NoInicializationException('No Inicialization maked', 'error');
    }
    /**
    * put your comment there...
    *
    * @param array $config
    * @param string $key
    * @param int $id
    */
    protected function distination(array $config, $key, $id)
    {
        if (! isset($this->dst[$config['type']][$id]) || ! $this->dst[$config['type']][$id]) {
            if ($config === []) {
                throw new NoDistinationException('No destination for element with key = '.$key, 'error');
            }
            $function = $config['type'].'Adapter';
            $filesystem = new Filesystem($this->{$function}($config));
            MyLog::info('Setted config', $config, 'main');
            $filesystem->addPlugin(new SyncFiles());
            $this->dst[$config['type']][$id] = $filesystem;
        }
        return $this->dst[$config['type']][$id];
    }
    /**
    * put your comment there...
    *
    * @param array $config
    * @return \League\Flysystem\Adapter\Ftp
    */
    protected function ftpAdapter(array $config)
    {
        return new Ftp([
            'host' => $config['host'],
            'username' => $config['user'],
            'password' => $config['pass'],

            /** optional config settings */
            'passive' => true,
        ]);
    }
}
