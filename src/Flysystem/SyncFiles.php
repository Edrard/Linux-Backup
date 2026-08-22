<?php

namespace Flysystem;

use edrard\Log\MyLog;
use Exc\SyncException;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemInterface;
use League\Flysystem\PluginInterface;

class SyncFiles implements PluginInterface
{
    protected $filesystem;
    protected $local;
    protected $src_path;
    protected $dst_path;
    protected $contents = [];
    protected $dst = [];
    protected $dir_create = [];
    protected $dir_delete = [];
    protected $file_create = [];
    protected $file_delete = [];
    protected $exclude = [];
    protected $parent = '';

    /**
    * put your comment there...
    *
    * @param FilesystemInterface $filesystem
    */
    public function setFilesystem(FilesystemInterface $filesystem)
    {
        $this->filesystem = $filesystem;
    }
    /**
    * put your comment there...
    *
    */
    public function getMethod()
    {
        return 'syncFiles';
    }
    /**
    * put your comment there...
    *
    * @param Filesystem $src
    * @param string $src_path
    * @param string $path
    * @param array $exclude
    * @param string $parent
    * @param bool $recursive
    */
    public function handle(Filesystem $src, $src_path, $path = null, array $exclude = [], $parent = '', $recursive = true)
    {
        $this->local = $src;
        $this->src_path = $src_path;
        $this->dst_path = $path === null ? '' : $path;
        $this->exclude = $exclude;
        $this->parent = $parent;
        $this->parent();
        try {
            $this->contents = $this->local->listContents($this->src_path, $recursive);
            $this->dst = $this->filesystem->listContents($this->dst_path, $recursive);
            $this->pathMorf();
            $this->contents = $this->filterExcluded($this->contents);
            $this->dst = $this->filterExcluded($this->dst);
            MyLog::info("[Sync] Check if exist", [], 'main');
            foreach ($this->contents as $way) {
                //Check if exist
                $this->checkIf($way, '_create', 'dst');
            }
            MyLog::info("[Sync] Check if delete", [], 'main');
            foreach ($this->dst as $out) {
                //Check if delete
                $this->checkIf($out, '_delete', 'contents');
            }
            MyLog::info("[Sync] Files deleting process starting", [], 'main');
            $this->deleteFiles();
            MyLog::info("[Sync] Directory deleting process starting", [], 'main');
            $this->deleteDirectory();
            MyLog::info("[Sync] Directory creating process starting", [], 'main');
            $this->createDirectory();
            MyLog::info("[Sync] Files copy process starting", [], 'main');
            $this->copyUpdateFiles();
        } catch (SyncException $error) {
            throw $error;
        } catch (\Exception $error) {
            MyLog::error('[SyncFiles] '.$error->getMessage(),[],'main');
            throw new SyncException($error->getMessage(), 'error');
        }
        $this->resset();
    }
    /**
    * put your comment there...
    *
    */
    protected function parent()
    {
        if ($this->parent) {
            $tmp = explode('/', trim($this->src_path, '/'));
            $this->parent = ! is_array($tmp) || (is_array($tmp) && ($tmp) === []) ? $this->src_path : array_pop($tmp);
        }
        $this->dst_path = trim($this->dst_path, '/').'/'.$this->parent;
    }
    /**
    * put your comment there...
    *
    */
    protected function pathMorf()
    {
        foreach ($this->dst as $key => $distination) {
            $this->dst[$key]['path'] = preg_replace('#^'.trim($this->dst_path, '/').'#iU', '', $distination['path']);
        }
        foreach ($this->contents as $key => $way) {
            $this->contents[$key]['path'] = preg_replace('#^'.trim($this->src_path, '/').'#iU', '', $way['path']);
        }
    }
    /**
    * Remove excluded folders from sync comparison.
    */
    protected function filterExcluded(array $contents)
    {
        if ($this->exclude === []) {
            return $contents;
        }
        return array_values(array_filter($contents, function ($item) {
            return ! $this->isExcluded($item['path']);
        }));
    }
    /**
    * Check if a relative path is inside an excluded folder.
    */
    protected function isExcluded($path)
    {
        $path = trim($path, '/');
        foreach ($this->exclude as $exclude) {
            $exclude = trim($exclude, '/');
            if ($exclude !== '' && ($path === $exclude || strpos($path, $exclude.'/') === 0)) {
                return true;
            }
        }
        return false;
    }
    /**
    * put your comment there...
    *
    * @param string $out
    * @param string $type
    * @param string $source
    *
    */
    protected function checkIf($out, $type, $source)
    {
        foreach ($this->{$source} as $directory) {
            if ($out['type'] == $directory['type'] && $out['path'] == $directory['path']) {
                if ($out['type'] == 'dir') {
                    return;
                } elseif (isset($out['basename']) && isset($directory['basename']) && $out['basename'] == $directory['basename']) {
                    if (isset($out['size']) && isset($directory['size'])) {
                        if ($out['size'] == $directory['size']) {
                            return;
                        }
                    } else {
                        return;
                    }
                }
            }
        }
        $create = $out['type'].$type;
        $this->{$create}[] = $out;
    }
    /**
    * put your comment there...
    *
    */
    protected function deleteDirectory()
    {
        foreach ($this->dir_delete as $del) {
            $dir = '/'.trim($this->dst_path, '/').$del['path'];
            $response = $this->filesystem->deleteDir($dir);
            $this->syncResponse($response, "[Sync] Folder Deleted", "[Sync] Can`t Delete Folder:", [$dir]);
        }
    }
    /**
    * put your comment there...
    *
    */
    protected function deleteFiles()
    {
        foreach ($this->file_delete as $del) {
            $file = '/'.trim($this->dst_path, '/').$del['path'];
            $response = $this->filesystem->delete($file);
            $this->syncResponse($response, "[Sync] File Deleted", "[Sync] Can`t Delete File:", [$file]);
        }
    }
    /**
    * put your comment there...
    *
    */
    protected function createDirectory()
    {
        foreach ($this->dir_create as $creat_dir) {
            $dir = '/'.trim($this->dst_path, '/').$creat_dir['path'].'/';
            //$dir = str_replace('.','rp_dot_',$dir);
            $response = $this->filesystem->createDir($dir);
            $this->syncResponse($response, "[Sync] Folder created", "[Sync] Can`t create folder:", [$dir]);
        }
    }
    /**
    * put your comment there...
    *
    */
    protected function copyUpdateFiles()
    {
        // $this->file_create
        foreach ($this->file_create as $file_create) {
            $file = '/'.trim($this->dst_path, '/').$file_create['path'];
            $local = '/'.trim($this->src_path, '/').$file_create['path'];
            $read = $this->local->readStream($local);
            if ($read === false) {
                throw new SyncException('Can not read local file: '.$local, 'error');
            }
            $response = $this->filesystem->putStream($file, $read);
            if (is_resource($read)) {
                fclose($read);
            }
            $this->syncResponse($response, "[Sync] File update/created", "[Sync] Can`t create/update file:", [$file]);
        }
    }
    /**
    * Log sync operation result and fail the run when remote action failed.
    */
    protected function syncResponse($response, $success, $error, array $context)
    {
        if ($response) {
            MyLog::info($success, $context, 'main');
            return;
        }
        MyLog::error($error, array_merge($context, [$response]), 'main');
        throw new SyncException($error.' '.implode(' ', $context), 'error');
    }
    /**
    * put your comment there...
    *
    */
    protected function resset()
    {
        $this->contents = [];
        $this->dst = [];
        $this->dir_create = [];
        $this->dir_delete = [];
        $this->file_create = [];
        $this->file_delete = [];
        $this->exclude = [];
        $this->parent = '';
    }
}
