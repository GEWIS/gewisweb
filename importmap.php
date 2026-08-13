<?php

/**
 * Returns the importmap for this application.
 *
 * - "path" is a path inside the asset mapper system. Use the
 *     "debug:asset-map" command to see the full list of paths.
 *
 * - "entrypoint" (JavaScript only) set to true for any module that will
 *     be used as an "entrypoint" (and passed to the importmap() Twig function).
 *
 * The "importmap:require" command can be used to add new entries to this file.
 *
 * @return array<string, array{    // Import name as key, description of the imported file as value
 *     path: string,               // Logical, relative or absolute path to the file
 *     type?: 'js'|'css'|'json',   // Type of the file, defaults to 'js'
 *     entrypoint?: bool,          // Whether the file is an entrypoint, for 'js' only
 * }|array{
 *     version: string,            // Version of the remote package
 *     package_specifier?: string, // Remote "package-name/path" specifier, defaults to the import name
 *     type?: 'js'|'css'|'json',
 *     entrypoint?: bool,
 * }>
 */
return [
    'app' => ['path' => './assets/app.js', 'entrypoint' => true],
    '@symfony/stimulus-bundle' => ['path' => './vendor/symfony/stimulus-bundle/assets/dist/loader.js'],
    '@symfony/ux-live-component' => ['path' => './vendor/symfony/ux-live-component/assets/dist/live_controller.js'],
    'ckeditor5' => ['path' => './assets/js/ckeditor5/ckeditor5.js'],
    'ckeditor5/translations/nl.js' => ['path' => './assets/js/ckeditor5/translations/nl.js'],
    '@gewis/splash' => ['version' => '2.4.0'],
    '@hotwired/stimulus' => ['version' => '3.2.2'],
    'photoswipe' => ['version' => '5.4.4'],
    'photoswipe/dist/photoswipe.min.css' => ['version' => '5.4.4', 'type' => 'css'],
    'altcha/dist/main/altcha.i18n.js' => ['version' => '3.2.1'],
    'photoswipe/lightbox' => ['version' => '5.4.4'],
    'tom-select' => ['version' => '2.6.2'],
    'tom-select/dist/css/tom-select.bootstrap5.css' => ['version' => '2.6.2', 'type' => 'css'],
    '@orchidjs/sifter' => ['version' => '1.1.0'],
    '@orchidjs/unicode-variants' => ['version' => '1.1.2'],
    'cropperjs' => ['version' => '2.1.1'],
    '@cropper/utils' => ['version' => '2.1.1'],
    '@cropper/elements' => ['version' => '2.1.1'],
    '@cropper/element' => ['version' => '2.1.1'],
    '@cropper/element-canvas' => ['version' => '2.1.1'],
    '@cropper/element-image' => ['version' => '2.1.1'],
    '@cropper/element-shade' => ['version' => '2.1.1'],
    '@cropper/element-handle' => ['version' => '2.1.1'],
    '@cropper/element-selection' => ['version' => '2.1.1'],
    '@cropper/element-grid' => ['version' => '2.1.1'],
    '@cropper/element-crosshair' => ['version' => '2.1.1'],
    '@cropper/element-viewer' => ['version' => '2.1.1'],
];
