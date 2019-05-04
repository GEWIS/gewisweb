<?php


namespace Decision\Controller\FileBrowser;

/**
 * Interface describing the operations required for reading browsing files (read-only).
 * Different implementations can fetch files from different locations.
 */
interface FileReader
{

    /**
     * Return an array of all files and subdirectories in the given directory.
     * Returns null when the $path doesn't resolve to a valid directory or
     * the operation fails otherwise.
     *
     * @param string $path
     * @return array[FileNode]|null
     */
    public function listDir($path);

    /**
     * Either redirects the user to a location to download the file in $path or
     * directly sends this file to the user.
     * Returns true on success, false on failure.
     *
     * @param string $path
     * @return bool
     */
    public function downloadFile($path);

    /**
     * Returns whether the given $path is valid
     * and is a directory.
     * @param string $path
     * @return bool
     */
    public function isDir($path);

    /**
     * Returns whether the given $path is allowed to be accessed.
     * @param string $path
     * @return bool
     */
    public function isAllowed($path);
}
