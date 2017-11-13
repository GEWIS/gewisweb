<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */
return [
    'doctrine' => [
        'connection' => [
            // Configuration for service `doctrine.connection.orm_default` service
            'orm_default' => [
                'driverClass' =>'Doctrine\DBAL\Driver\PDOMySql\Driver',
                'configuration' => 'orm_default',
                'eventmanager'  => 'orm_default',
                'params' => [
                    'host'     => 'webmysql',
                    'port'     => '3306',
                    'user'     => 'gewis',
                    'password' => 'gewis',
                    'dbname'   => 'gewis',
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
