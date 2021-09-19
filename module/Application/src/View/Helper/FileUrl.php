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
    protected $locator;

    /**
     * Get the file URL.
     *
     * @param string $path
     *
     * @return string
     */
    public function __invoke($path)
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
    protected function getServiceLocator()
    {
        return $this->locator;
    }

    /**
     * Set the service locator.
     *
     * @param ContainerInterface $locator
     */
    public function setServiceLocator($locator)
    {
        $this->locator = $locator;
    }
}
