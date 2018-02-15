<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Decision\Controller;

/**
 * Description of LocalFileReader
 *
 * @author s134399
 */
class LocalFileReader implements FileReader {
    static private $root;

    public function __construct($root) {
        $this->root = $root;
    }

    public function downloadFile($path) {
        return $path;
    }

    public function listDir($path) {
        return $path;
    }

}
