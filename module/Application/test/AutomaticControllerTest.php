<?php

namespace ApplicationTest;

use Laminas\Router\Exception\InvalidArgumentException;
use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Part;
use Laminas\Router\Http\Segment;
use Laminas\Router\Http\TreeRouteStack;
use Laminas\Router\PriorityList;
use RuntimeException;
use Traversable;

class AutomaticControllerTest extends BaseControllerTest
{
    public function testAllRoutes(): void
    {
        /** @var TreeRouteStack $router */
        $router = $this->getApplication()->getServiceManager()->get('router');
        /** @var PriorityList|Traversable $routes */
        $routes = $router->getRoutes();
        $this->parsePriorityList($routes);
    }

    protected function parsePriorityList(PriorityList $list): void
    {
        foreach ($list as $element) {
            if ($element instanceof Part) {
                $this->parsePart($element);
            } elseif ($element instanceof Literal) {
                $this->parseLiteral($element);
            } elseif ($element instanceof Segment) {
                $this->parseSegment($element);
            } else {
                throw new RuntimeException(
                    sprintf(
                        'Unexpected type in parsePriorityList: %s',
                        get_class($element),
                    )
                );
            }
        }
    }

    protected function parsePart(Part $part): void
    {
        $routes = $part->getRoutes();
        if ($routes instanceof PriorityList) {
            $this->parsePriorityList($routes);
        } else {
            throw new RuntimeException(
                sprintf(
                    'Unexpected type in parsePart: %s',
                    get_class($routes),
                )
            );
        }
    }

    protected function parseSegment(Segment $segment): void
    {
        $params = $this->getParams();
        try {
            $url = $segment->assemble($params);
            if (is_string($url)) {
                $this->testRoute($url);
            } else {
                throw new RuntimeException(
                    sprintf(
                        'Unexpected type in parseSegment: %s',
                        get_class($url),
                    )
                );
            }
        } catch (InvalidArgumentException $exception) {
            $this->addWarning(
                "Skipping one or multiple route segments because required parameters could not be generated automatically."
            );
            $this->addWarning($exception->getMessage());
            $this->addWarning(var_export($segment, true));
        }
    }

    protected function parseLiteral(Literal $literal): void
    {
        $url = $literal->assemble();
        if (is_string($url)) {
            $this->testRoute($url);
        } else {
            throw new RuntimeException(
                sprintf(
                    'Unexpected type in parseLiteral: %s',
                    get_class($url),
                )
            );
        }
    }

    protected function testRoute(string $url): void
    {
        $this->testRouteGet($url);
        $this->testRoutePost($url);
    }

    protected function testRouteGet(string $url): void
    {
        $this->dispatch($url, 'GET');
        $this->assertNotResponseStatusCode(500);
    }

    protected function testRoutePost(string $url): void
    {
        $this->dispatch($url, 'POST');
        $this->assertNotResponseStatusCode(500);
    }

    protected function getParams(): array
    {
        $params = array();
        $params['appId'] = 0;
        $params['type'] = 'committee';
        $params['abbr'] = 'wc';
        $params['category'] = 'vacancies';
        return $params;
    }
}
