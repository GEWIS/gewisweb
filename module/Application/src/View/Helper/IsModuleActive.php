<?php

declare(strict_types=1);

namespace Application\View\Helper;

use Laminas\View\Helper\AbstractHelper;
use Psr\Container\ContainerInterface;

use function array_map;
use function explode;
use function in_array;
use function is_array;
use function is_string;
use function preg_replace;
use function str_replace;

class IsModuleActive extends AbstractHelper
{
    public function __construct(protected readonly ContainerInterface $container)
    {
    }

    /**
     * Get the active module.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    public function __invoke(array $condition): bool
    {
        $info = $this->getRouteInfo();
        $satisfied = [];

        foreach ($condition as $key => $cond) {
            if (!isset($info[$key])) {
                return false;
            }

            if (!is_array($cond)) {
                $cond = [$cond];
            }

            // Keep track if satisfied for this level.
            $satisfied[$key] = false;

            foreach ($cond as $mini) {
                if ($satisfied[$key]) {
                    // Already satisfied, so break early.
                    break;
                }

                if (!is_string($mini)) {
                    // Not a string, we cannot check this.
                    continue;
                }

                if ($info[$key] !== $mini) {
                    continue;
                }

                $satisfied[$key] = true;
            }
        }

        return false === in_array(false, $satisfied, true);
    }

    /**
     * Get the module.
     *
     * @return string[]
     */
    public function getRouteInfo(): array
    {
        $match = $this->container->get('application')->getMvcEvent()->getRouteMatch();

        if (null === $match) {
            return [];
        }

        $controller = preg_replace(
            '/Controller$/',
            '',
            str_replace(
                '\\Controller',
                '',
                $match->getParam('controller'),
            ),
        );
        $controllerArray = explode('\\', $controller);

        if (null !== ($action = $match->getParam('action'))) {
            $controllerArray[] = $action;
        }

        return array_map(
            'strtolower',
            $controllerArray,
        );
    }
}
