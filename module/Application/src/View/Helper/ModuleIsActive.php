<?php

declare(strict_types=1);

namespace Application\View\Helper;

use Laminas\View\Helper\AbstractHelper;
use Psr\Container\ContainerInterface;

use function array_map;
use function explode;
use function str_replace;

class ModuleIsActive extends AbstractHelper
{
    /**
     * Service locator.
     */
    protected ContainerInterface $locator;

    /**
     * Get the active module.
     *
     * @param string[] $condition
     *
     * @return bool $condition
     */
    public function __invoke(array $condition): bool
    {
        $info = $this->getRouteInfo();
        foreach ($condition as $key => $cond) {
            if (
                !isset($info[$key])
                || (
                    null !== $cond
                    && $info[$key] !== $cond
                )
            ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the module.
     *
     * @return string[]
     */
    public function getRouteInfo(): array
    {
        $match = $this->getServiceLocator()->get('application')->getMvcEvent()->getRouteMatch();

        if (null === $match) {
            return [];
        }

        $controller = str_replace('\\Controller', '', $match->getParam('controller'));

        return array_map('strtolower', explode('\\', $controller));
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
