<?php
return [
    'doctrine' => [
        'connection' => [
            // Configuration for service `doctrine.connection.orm_default` service
            'orm_default' => [
                'driverClass' =>'Doctrine\DBAL\Driver\PDOMySql\Driver',
                'configuration' => 'orm_default',
                'eventmanager'  => 'orm_default',
                'params' => [
                    'host'     => 'mysql',
                    'port'     => '3306',
                    'user'     => 'gewis',
                    'password' => 'gewis',
                    'dbname'   => 'gewis',
                    'serverVersion' => '5.7'
                ]
            ],
        ],
        // Configuration details for the ORM.
        // See http://docs.doctrine-project.org/en/latest/reference/configuration.html
        'configuration' => [
            // Configuration for service `doctrine.configuration.orm_default` service
            'orm_default' => [
                // metadata cache instance to use. The retrieved service name will
                // be `doctrine.cache.$thisSetting`
                'metadata_cache'    => 'array',
                // DQL queries parsing cache instance to use. The retrieved service
                // name will be `doctrine.cache.$thisSetting`
                'query_cache'       => 'array',
                // ResultSet cache to use.  The retrieved service name will be
                // `doctrine.cache.$thisSetting`
                'result_cache'      => 'array',
                // Hydration cache to use.  The retrieved service name will be
                // `doctrine.cache.$thisSetting`
                'hydration_cache'   => 'array',
                // Generate proxies automatically (turn off for production)
                'generate_proxies'  => true,
                // directory where proxies will be stored. By default, this is in
                // the `data` directory of your application
                'proxy_dir'         => 'data/DoctrineORMModule/Proxy',
                // namespace for generated proxy classes
                'proxy_namespace'   => 'DoctrineORMModule\Proxy',
                // Custom DQL functions.
                'numeric_functions' => [
                    'RAND'  => 'Application\Extensions\Doctrine\Rand'
                ],
                // Second level cache configuration (see doc to learn about configuration)
                'second_level_cache' => []
            ]
        ],
    ],
];
