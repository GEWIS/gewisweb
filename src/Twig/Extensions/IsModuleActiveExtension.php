<?php

declare(strict_types=1);

namespace App\Twig\Extensions;

use Override;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

use function array_all;
use function array_filter;
use function array_map;
use function array_values;
use function explode;
use function preg_replace;
use function strtolower;

class IsModuleActiveExtension extends AbstractExtension
{
    public function __construct(private readonly RequestStack $requestStack)
    {
    }

    /**
     * @return TwigFunction[]
     */
    #[Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'is_module_active',
                $this->isModuleActive(...),
            ),
        ];
    }

    /**
     * @param string[] $condition
     */
    public function isModuleActive(array $condition): bool
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            return false;
        }

        $controller = $request->attributes->get('_controller');
        if (
            null === $controller
            || '' === $controller
            || 'error_controller' === $controller
        ) {
            return false;
        }

        [
            $class, $action
        ] = explode(
            '::',
            $controller,
        );
        $segments = explode(
            '\\',
            $class,
        );
        $filtered = array_filter(
            $segments,
            static function (string $s) {
                return 'app' !== strtolower($s) && 'controller' !== strtolower($s);
            },
        );
        $filtered[] = strtolower($action);

        $info = array_values(
            array_map(
                static function (string $segment) {
                    return preg_replace(
                        '/controller$/',
                        '',
                        strtolower($segment),
                    );
                },
                $filtered,
            ),
        );

        return array_all(
            $condition,
            static function ($expectedValue, $key) use ($info) {
                return isset($info[$key]) && $info[$key] === strtolower($expectedValue);
            },
        );
    }
}
