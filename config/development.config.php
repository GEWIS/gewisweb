<?php
return [
    // This should be an array of module namespaces used in the application.
    'modules' => [
        'Laminas\DeveloperTools',
        'TestData'
    ],
    'module_listener_options' => [
        'config_cache_enabled' => false,
        'module_map_cache_enabled' => false,
    ]
];
