<?php

declare(strict_types=1);

namespace Application\View\Helper;

use Laminas\View\Helper\AbstractHelper;
use Psr\Container\ContainerInterface;

class FileUrl extends AbstractHelper
{
    /**
     * Service locator.
     */
    protected ContainerInterface $locator;

    /**
     * Get the file URL.
     */
    public function __invoke(string $path): string
    {
        $config = $this->getServiceLocator()->get('config');
        $basedir = $config['storage']['public_dir'];

        return $this->getView()->basePath() . '/' . $basedir . '/' . $path;
    }

    /**
     * Get the service locator.
     */
    protected function getServiceLocator(): ContainerInterface
    {
        return $this->locator;
    }

    /**
     * Set the service locator.
     */
    public function setServiceLocator(ContainerInterface $locator): void
    {
        $this->locator = $locator;
    }
}
