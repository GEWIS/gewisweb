<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Decision\Controller;

/**
 * A Dummy FileReader implementation to test the UI
 */
class DummyReader implements FileReader {

    private $filesystem;

    public function __construct() {
        $this->filesystem = [[new FileNode('dir', '/', 'myFolder'), [
        [new FileNode('dir', '/myFolder', 'NestedFolder'), []],
        [new FileNode('file', '/myFolder', 'A-file'), []],
        [new FileNode('file', '/myFolder', 'Another-file'), []]
    ]],
        [new FileNode('file', '/', 'rootlevelfilez'),[]]
        ];
    }

    public function downloadFile($path) {
        $options = [
            'rootlevelfilez' => "WOOT WOOT GOT ROOT",
            'myFolder/A-file' => 'Just a file',
            'myFolder/Another-file' => 'Just another file'
        ];
        if (array_key_exists($path, $options)){
            var_dump($options[$path]);
            return true;
        }
        return false;
    }

    public function listDir($path) {
        $options = [
            '' => [ new FileNode('dir', 'myFolder/', 'myFolder'), new FileNode('file', 'rootlevelfilez', 'rootlevelfilez')],
            'myFolder/' => [new FileNode('dir', 'myFolder/NestedFolder/', 'NestedFolder'), new FileNode('file', 'myFolder/A-file', 'A-file'), new FileNode('file', 'myFolder/Another-file', 'Another-file')],
            'myFolder/NestedFolder/' => [],
        ];
        if (array_key_exists($path, $options)) {
            return $options[$path];
        }
        var_dump('misdir');
        return null;
    }

    public function isDir($path) {
        return in_array($path, ['', 'myFolder/', 'myFolder/NestedFolder/']);
    }

}
