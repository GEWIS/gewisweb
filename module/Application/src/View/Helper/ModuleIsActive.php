<?php

namespace Application\View\Helper;

use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\View\Helper\AbstractHelper;
use Interop\Container\ContainerInterface;

class ModuleIsActive extends AbstractHelper
{
    /**
     * Service locator.
     *
     * @var ServiceLocatorInterface
     */
    protected $locator;

    /**
     * Get the active module.
     *
     * @return bool $condition
     */
    public function __invoke($condition)
    {
        $info = $this->getRouteInfo();
        foreach ($condition as $key => $cond) {
            if (!isset($info[$key]) || (!is_null($cond) && $info[$key] != $cond)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the module.
     */
    public function getRouteInfo()
    {
        $match = $this->getServiceLocator()->get('application')
            ->getMvcEvent()->getRouteMatch();

        if (is_null($match)) {
            return [];
        }
        $controller = str_replace('\\Controller', '', $match->getParam('controller'));

        return array_map('strtolower', explode('\\', $controller));
    }

    /**
     * Get the service locator.
     *
     * @return ContainerInterface
     */
    public function getServiceLocator()
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
