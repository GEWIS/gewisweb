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
        $parts = explode('.', $path);
        $fileType = end($parts);
        $storagePath = $directory . '/' . substr($hash, 2) . '.' . strtolower($fileType);

        return $storagePath;
    }

    /**
     * Stores files in the content based file system
     *
     * @param string $source
     * @param bool $isUploaded Indicates whether the file to be stored is an uploaded file.
     *
     * @return string the path at which the file was stored.
     */
    public function storeFile($source, $isUploaded = true)
    {
        $config = $this->getConfig();
        $storagePath = $this->generateStoragePath($source);
        $destination = $config['storage_dir'] . $storagePath;
        if(!file_exists($destination)) {
            if($isUploaded) {
                move_uploaded_file($source, $destination);
            } else {
                copy($source, $destination);
            }
        }

        return $storagePath;
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