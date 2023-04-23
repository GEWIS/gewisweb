<?php

declare(strict_types=1);

namespace Application\View\Helper;

use Laminas\View\Helper\AbstractHelper;
use Psr\Container\ContainerInterface;

class ModuleIsActive extends AbstractHelper
{
    /**
     * Service locator.
     *
     * @var ContainerInterface
     */
    protected ContainerInterface $locator;

    /**
     * Get the active module.
     *
     * @param array $condition
     *
     * @return bool $condition
     */
    public function __invoke(array $condition): bool
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
     *
     * @return array
     */
    public function getRouteInfo(): array
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
