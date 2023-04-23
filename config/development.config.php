<?php

declare(strict_types=1);

return [
    // This should be an array of module namespaces used in the application.
    'modules' => [
        'Laminas\DeveloperTools',
        'SanSessionToolbar',
    ],
    'module_listener_options' => [
        'config_glob_paths' => [realpath(__DIR__) . '/autoload/{,*.}{global,local}-development.php'],
        'config_cache_enabled' => false,
        'module_map_cache_enabled' => false,
    ],
];
