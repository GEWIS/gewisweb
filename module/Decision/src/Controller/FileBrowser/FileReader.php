<?php

declare(strict_types=1);

namespace Decision\Controller\FileBrowser;

use Laminas\Http\Response\Stream;

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
     * @return ?FileNode[]
     */
    public function listDir(string $path): ?array;

    /**
     * Either redirects the user to a location to download the file in $path or
     * directly sends this file to the user. Returns false on failure.
     */
    public function downloadFile(string $path): bool|Stream;

    /**
     * Returns whether the given $path is valid
     * and is a directory.
     */
    public function isDir(string $path): bool;

    /**
     * Returns whether the given $path is allowed to be accessed.
     */
    public function isAllowed(string $path): bool;
}
