<?php

namespace Application\View\Helper;

use Laminas\View\Helper\AbstractHelper;
use Interop\Container\ContainerInterface;

class FileUrl extends AbstractHelper
{
    /**
     * Service locator.
     *
     * @var ContainerInterface
     */
    protected ContainerInterface $locator;

    /**
     * Get the file URL.
     *
     * @param string $path
     *
     * @return string
     */
    public function __invoke(string $path): string
    {
        $config = $this->getServiceLocator()->get('config');
        $basedir = $config['storage']['public_dir'];

        return $this->getView()->basePath() . '/' . $basedir . '/' . $path;
    }

    /**
     * Get the service locator.
     *
     * @return ContainerInterface
     */
    protected function getServiceLocator(): ContainerInterface
    {
        return $this->locator;
    }

    /**
     * Set the service locator.
     *
     * @param ContainerInterface $locator
     */
    public function setServiceLocator(ContainerInterface $locator): void
    {
        $this->locator = $locator;
    }
}
