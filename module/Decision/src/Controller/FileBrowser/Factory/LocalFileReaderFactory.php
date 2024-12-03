<?php

declare(strict_types=1);

namespace Decision\Controller\FileBrowser\Factory;

use Decision\Controller\FileBrowser\LocalFileReader;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class LocalFileReaderFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): LocalFileReader {
        $config = $container->get('config')['filebrowser'];

        return new LocalFileReader(
            $config['folder'],
            $config['valid_file'],
        );
    }
}
