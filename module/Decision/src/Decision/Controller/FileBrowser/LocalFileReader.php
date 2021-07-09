<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Decision\Controller\FileBrowser;

use Zend\Http\Headers;
use Zend\Http\Response\Stream;

/**
 * Description of LocalFileReader
 *
 * @author s134399
 */
class LocalFileReader implements FileReader
{
    /**
     * The location in the local filesystem that is considered the 'root' for this browser
     * @var string
     */
    private $root;

    /**
     * A regex pattern matching all valid filepaths.
     * @var string
     */
    private $validFilepath;

    public function __construct($root, $validFilepath)
    {
        $this->root = $root;
        $this->validFilepath = $validFilepath;
    }

    public function downloadFile($path)
    {
        $fullPath = $this->root . $path;
        if (!is_file($fullPath) || !$this->isValidPathName($fullPath)) {
            return null;
        }
        $contentType = mime_content_type($fullPath);
        if (substr($contentType, 0, strlen('text')) === 'text') {
            $contentType = 'text/plain';
        }
        $response = new Stream();
        $response->setStream(fopen('file://' . $fullPath, 'r'));
        $response->setStatusCode(200);
        $headers = new Headers();
        $array = explode('/', $fullPath);
        $headers->addHeaderLine('Content-Type', $contentType)
            ->addHeaderLine('Content-Disposition', 'filename="' . end($array) . '"')
            ->addHeaderLine('Content-Length', filesize($fullPath));
        $response->setHeaders($headers);
        return $response;
    }

    public function listDir($path)
    {
        //remove the trailing slash from the dir
        $fullPath = $this->root . $path;
        if (!is_dir($fullPath)) {
            return null;
        }
        //We can insert an additional /, except when when $path is the root
        $delimiter = $path !== '' ? '/' : '';
        $dircontents = scandir($fullPath);
        $files = [];
        foreach ($dircontents as $dircontent) {
            $kind = $this->interpretDircontent($dircontent, $fullPath . '/' . $dircontent);
            if ($kind === false) {
                continue;
            }
            $files[] = new FileNode(
                $kind,
                $path . $delimiter . $dircontent,
                $dircontent
            );
        }
        return $files;
    }

    protected function interpretDircontent($dircontent, $fullPath)
    {
        if ($dircontent[0] === '.' || !$this->isValidPathName($fullPath)) {
            return false;
        }
        if (is_link($fullPath)) {
            //symlink could point to illegal location, we must check this
            if (!$this->isAllowed(substr($fullPath, strlen($this->root)))) {
                return false;
            }
            return $this->interpretDircontent($dircontent, realpath($fullPath));
        }
        if (is_dir($fullPath)) {
            return 'dir';
        }
        if (is_file($fullPath)) {
            return 'file';
        }
        //Unknown filesystem entity
        //(likely, the path doesn't resolve to a valid entry in the filesystem at all)
        return false;
    }

    public function isDir($path)
    {
        return is_dir($this->root . $path);
    }

    public function isAllowed($path)
    {
        $fullPath = $this->root . $path;
        if (!is_readable($fullPath) || !$this->isValidPathName($path)) {
            return false;
        }
        $realFullPath = realpath($fullPath);
        $realRoot = realpath($this->root);
        //Check whether the real location of fullPath is in a subdir of our 'root'.
        if (substr($realFullPath, 0, strlen($realRoot)) !== $realRoot) {
            return false;
        }
        return true;
    }

    protected function isValidPathName($path)
    {
        return preg_match('#^' . $this->validFilepath . '$#', $path) === 1;
    }
}
