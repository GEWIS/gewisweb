<?php

namespace Decision\Controller\FileBrowser;

use Laminas\Http\Headers;
use Laminas\Http\Response\Stream;

/**
 * Description of LocalFileReader.
 *
 * @author s134399
 */
class LocalFileReader implements FileReader
{
    public function __construct(
        private readonly string $root,
        private readonly string $validFilepath,
    ) {
    }

    /**
     * @param string $path
     *
     * @return bool|Stream
     */
    public function downloadFile(string $path): bool|Stream
    {
        $fullPath = $this->root . $path;

        if (!is_file($fullPath) || !$this->isValidPathName($fullPath)) {
            return false;
        }

        $contentType = mime_content_type($fullPath);

        if (str_starts_with($contentType, 'text')) {
            $contentType = 'text/plain';
        }

        $response = new Stream();
        $response->setStream(fopen('file://' . $fullPath, 'r'));
        $response->setStatusCode(200);

        $headers = new Headers();
        $array = explode('/', $fullPath);
        $headers->addHeaderLine('Content-Type', $contentType)
            ->addHeaderLine('Content-Disposition', 'filename="' . end($array) . '"')
            ->addHeaderLine('Content-Length', strval(filesize($fullPath)));
        $response->setHeaders($headers);

        return $response;
    }

    /**
     * @param string $path
     *
     * @return array|null
     */
    public function listDir(string $path): ?array
    {
        //remove the trailing slash from the dir
        $fullPath = $this->root . $path;

        if (!is_dir($fullPath)) {
            return null;
        }

        //We can insert an additional /, except when when $path is the root
        $delimiter = '' !== $path ? '/' : '';
        $dirContents = scandir($fullPath);
        $files = [];

        foreach ($dirContents as $dirContent) {
            $kind = $this->interpretDircontent($dirContent, $fullPath . '/' . $dirContent);

            if (false === $kind) {
                continue;
            }

            $files[] = new FileNode(
                $kind,
                $path . $delimiter . $dirContent,
                $dirContent,
            );
        }

        return $files;
    }

    /**
     * @param string $dirContent
     * @param string $fullPath
     *
     * @return false|string
     */
    protected function interpretDirContent(
        string $dirContent,
        string $fullPath,
    ): bool|string {
        if ('.' === $dirContent[0] || !$this->isValidPathName($fullPath)) {
            return false;
        }

        if (is_link($fullPath)) {
            //symlink could point to illegal location, we must check this
            if (!$this->isAllowed(substr($fullPath, strlen($this->root)))) {
                return false;
            }

            return $this->interpretDirContent($dirContent, realpath($fullPath));
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

    /**
     * @param string $path
     *
     * @return bool
     */
    public function isDir(string $path): bool
    {
        return is_dir($this->root . $path);
    }

    /**
     * @param string $path
     *
     * @return bool
     */
    public function isAllowed(string $path): bool
    {
        $fullPath = $this->root . $path;

        if (!is_readable($fullPath) || !$this->isValidPathName($path)) {
            return false;
        }

        $realFullPath = realpath($fullPath);
        $realRoot = realpath($this->root);

        //Check whether the real location of fullPath is in a subdir of our 'root'.
        if (!str_starts_with($realFullPath, $realRoot)) {
            return false;
        }

        return true;
    }

    /**
     * @param string $path
     *
     * @return bool
     */
    protected function isValidPathName(string $path): bool
    {
        return 1 === preg_match('#^' . $this->validFilepath . '$#', $path);
    }
}
