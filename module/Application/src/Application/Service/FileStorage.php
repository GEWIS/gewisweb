<?php

namespace Application\Service;

/**
 * File storage service. This service can be used to safely store files without
 * having to worry about file names.
 *
 * @package Application\Service
 */
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
            mkdir($config['storage_dir'] . '/' . $directory, $config['dir_mode']);
        }

        $storagePath = $directory . '/' . substr($hash, 2);

        return $storagePath;
    }

    /**
     * Stores an uploaded file in the content based file system.
     *
     * @param array $file
     *
     * @return string The CFS path at which the file was stored
     * @throws \Exception
     */
    public function storeUploadedFile($file)
    {
        $config = $this->getConfig();
        if ($file['error'] !== 0) {
            throw new \Exception(
                sprintf(
                    $this->getTranslator()->translate('An unknown error occurred during uploading (%i)'),
                    $file['error']
                )
            );
        }

        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $storagePath = $this->generateStoragePath($file['tmp_name']) . '.' . $extension;
        $destination = $config['storage_dir'] . '/' . $storagePath;
        if (!file_exists($destination)) {
            move_uploaded_file($file['tmp_name'], $destination);
        } else {
            unlink($file['tmp_name']);
        }

        return $storagePath;

    }

    /**
     * Stores files in the content based file system
     *
     * @param string $source The source file to store
     * @param boolean $move indicating whether the file should be moved or copied
     *
     * @return string the path at which the file was stored.
     */
    public function storeFile($source, $move = true)
    {
        $config = $this->getConfig();
        $extension = pathinfo($source, PATHINFO_EXTENSION);
        $storagePath = $this->generateStoragePath($source) . '.' . $extension;
        $destination = $config['storage_dir'] . '/' . $storagePath;
        if (!file_exists($destination)) {
            if ($move) {
                rename($source, $destination);
            } else {
                copy($source, $destination);
            }
        } elseif ($move) {
            unlink($source);
        }

        return $storagePath;
    }

    /**
     * Removes a file from the content based file system.
     *
     * @param string $path The CFS path of the file to remove
     *
     * @return bool indicating if removing the file was successful.
     */
    public function removeFile($path)
    {
        $config = $this->getConfig();
        $fullPath = $config['storage_dir'] . '/' . $path;

        if (file_exists($fullPath)) {
            return unlink($fullPath);
        } else {
            return false;
        }
    }

    /**
     * Returns a response suitable for offering a file download.
     * In most modern browsers this function will cause the browser to display
     * the file and give the user the option to save it.
     *
     * @param string $path The CFS path of the file to download
     * @param string $fileName The file name to give the downloaded file
     *
     * @return \Zend\Http\Response\|null If the given file is not found, null is returned
     */
    public function downloadFile($path, $fileName)
    {
        $config = $this->getConfig();

        $file = $config['storage_dir'] . '/' . $path;

        if (!file_exists($file)) {
            return null;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $type = finfo_file($finfo, $file);
        finfo_close($finfo);

        $response = new \Zend\Http\Response\Stream();
        $response->setStream(fopen($file, 'r'));
        $response->setStatusCode(200);
        $response->setStreamName($fileName);
        $headers = new \Zend\Http\Headers();
        $headers->addHeaders([
            // Suggests to the browser to display the file instead of saving
            'Content-Disposition' => 'inline; filename="' . $fileName . '"',
            'Content-Type' => $type,
            'Content-Length' => filesize($file),
            // zf2 parses date as a string for a \DateTime() object:
            'Expires' => '+1 year',
            'Cache-Control' => 'public',
            'Pragma' => ''
        ]);
        $response->setHeaders($headers);

        return $response;
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
