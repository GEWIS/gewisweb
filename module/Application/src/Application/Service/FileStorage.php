<?php

namespace Application\Service;


class FileStorage extends AbstractService
{
    /**
     * Generates CFS paths
     *
     * @param string $path The path of the photo to generate the path for
     *
     * @return string the path at which the photo should be saved
     */
    public function generateStoragePath($path)
    {
        $config = $this->getConfig();
        $hash = sha1_file($path);
        /**
         * the hash is split to obtain a path
         * like 92/cfceb39d57d914ed8b14d0e37643de0797ae56.jpg
         */
        $directory = substr($hash, 0, 2);
        if (!file_exists($config['storage_dir'] . '/' . $directory)) {
            mkdir($config['storage_dir'] . '/' . $directory);
        }

        $storagePath = $directory . '/' . substr($hash, 2);

        return $storagePath;
    }

    /**
     * Stores files in the content based file system
     *
     * @param string $source
     * @param bool $isUploaded Indicates whether the file to be stored is an uploaded file.
     * @param string $extension optional extension in the case that the file has no extension
     *
     * @return string the path at which the file was stored.
     */
    public function storeFile($source, $isUploaded = true, $extension = null)
    {
        $config = $this->getConfig();
        $storagePath = $this->generateStoragePath($source);
        if (is_null($extension)) {
            $parts = explode('.', $source);
            $extension = end($parts);
        }

        $destination = $config['storage_dir'] . '/' .  $storagePath . '.' . $extension;
        if(!file_exists($destination)) {
            if($isUploaded) {
                move_uploaded_file($source, $destination);
            } else {
                copy($source, $destination);
            }
        }

        return $storagePath;
    }

    public function removeFile($path)
    {
        $config = $this->getConfig();
        $fullPath = $config['storage_dir'] . '/' . $path;

        if(file_exists($fullPath)) {
            return unlink($fullPath);
        } else {
            return false;
        }
    }

    /**
     * Get the storage config, as used by this service.
     *
     * @return array containing the config for the module
     */
    public function getConfig()
    {
        $config = $this->sm->get('config');

        return $config['storage'];
    }
}