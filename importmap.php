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
 */
return [
    'app' => [
        'path' => './assets/app.js',
        'entrypoint' => true,
    ],
    '@symfony/stimulus-bundle' => [
        'path' => './vendor/symfony/stimulus-bundle/assets/dist/loader.js',
    ],
    '@symfony/ux-live-component' => [
        'path' => './vendor/symfony/ux-live-component/assets/dist/live_controller.js',
    ],
    '@gewis/splash' => [
        'version' => '2.4.0',
    ],
    '@hotwired/stimulus' => [
        'version' => '3.2.2',
    ],
    'photoswipe' => [
        'version' => '5.4.4',
    ],
    'photoswipe/dist/photoswipe.min.css' => [
        'version' => '5.4.4',
        'type' => 'css',
    ],
    'altcha/dist/main/altcha.i18n.js' => [
        'version' => '3.0.11',
    ],
    // Self-contained CKEditor 5 browser bundle, vendored under assets/js/ (the npm package's entry re-exports the
    // @ckeditor/* source tree, which the importmap resolver cannot ESM-ify). Loaded lazily by markdown-editor.
    'ckeditor5' => [
        'path' => './assets/js/ckeditor5/ckeditor5.js',
    ],
    // CKEditor 5 ships with English built in; the Dutch UI is a separate translations module, loaded lazily by
    // markdown-editor when the page locale is `nl`.
    'ckeditor5/translations/nl.js' => [
        'path' => './assets/js/ckeditor5/translations/nl.js',
    ],
];
