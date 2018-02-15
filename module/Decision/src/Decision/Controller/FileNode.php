<?php

namespace Decision\Controller;

/**
 * Represents a node in a filesystem, which is either a file, or a directory.
 * Immutable.
 */
class FileNode {

    /**
     * Whether the node represents a file or a directory
     * @var string  either 'file' or 'dir'
     */
    protected $kind;

    /**
     * The path of the parent containing this node
     * @var string a valid path relative to some root
     */
    protected $fullPath;

    /**
     * The name of this file or dir
     * @var string valid name in filesystem
     */
    protected $name;

    public function __construct($kind, $fullPath, $name) {
        if (!($kind==='dir' ||  $kind==='file')) {
            //invalid kind
            return false;
        }
        $this->kind = $kind;
        $this->fullPath = $fullPath;
        $this->name = $name;
    }

    /**
     * Gets kind
     * @return string either 'file' or 'dir'
     */
    public function getKind() {
        return $this->kind;
    }

    /**
     * Gets path of parent
     * @return string valid path
     */
    public function getFullPath() {
        return $this->fullPath;
    }

    /**
     * Name of file or dir
     * @return string valid name
     */
    public function getName() {
        return $this->name;
    }
}
