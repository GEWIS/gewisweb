<?php

declare(strict_types=1);

namespace ApplicationTest;

use Doctrine\DBAL\Driver\PDO\SQLite\Driver;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;
use Laminas\ServiceManager\ServiceManager;
use Laminas\Stdlib\ArrayUtils;

class TestConfigProvider
{
    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
     */
    public static function getConfig(): array
    {
        return include './config/application.config.php';
    }

    /**
     * @throws SchemaException
     */
    public static function overrideConfig(ServiceManager $serviceManager): void
    {
        $testConfig = [
            'doctrine' => [
                'connection' => [
                    'orm_default' => [
                        'driverClass' => Driver::class,
                        'params' => [
                            'user' => 'phpunit',
                            'password' => 'phpunit',
                            'memory' => true,
                            'charset' => 'utf8mb4',
                            'collate' => 'utf8mb4_unicode_ci',
                        ],
                    ],
                ],
            ],
        ];

        $appConfig = $serviceManager->get('config');

        $appConfig = ArrayUtils::merge($appConfig, $testConfig);

        $serviceManager->setService('config', $appConfig);

        /** @var EntityManager $entityManager */
        $entityManager = $serviceManager->get('doctrine.entitymanager.orm_default');
        $metadatas = $entityManager->getMetadataFactory()->getAllMetadata();
        if (empty($metadatas)) {
            throw new SchemaException(
                'No metadata classes to process',
            );
        }

        $tool = new SchemaTool($entityManager);
        $tool->createSchema($metadatas);
    }
}
