<?php

namespace Application\View\Helper;

use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\View\Helper\AbstractHelper;

class FileUrl extends AbstractHelper
{
    /**
     * Service locator.
     *
     * @var ServiceLocatorInterface
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

        return $this->getView()->basePath().'/'.$basedir.'/'.$path;
    }

    /**
     * Get the service locator.
     *
     * @return ServiceLocatorInterface
     */
    public function getServiceLocator()
    {
        return $this->locator;
    }

    /**
     * Set the service locator.
     *
     * @param ServiceLocatorInterface
     */
    public function setServiceLocator($locator)
    {
        $this->locator = $locator;
    }
}
