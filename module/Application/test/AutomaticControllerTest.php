<?php

declare(strict_types=1);

namespace ApplicationTest;

use Application\Router\LanguageAwareTreeRouteStack;
use Iterator;
use Laminas\Router\Exception\InvalidArgumentException;
use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Method;
use Laminas\Router\Http\Part;
use Laminas\Router\Http\Regex;
use Laminas\Router\Http\Segment;
use Laminas\Router\PriorityList;
use RuntimeException;
use Throwable;

use function is_string;
use function serialize;
use function sprintf;

class AutomaticControllerTest extends BaseControllerTest
{
    public function testAllRoutes(): void
    {
        /** @var LanguageAwareTreeRouteStack $router */
        $router = $this->getApplication()->getServiceManager()->get('router');
        /** @var Iterator $routes */
        $routes = $router->getRoutes();
        $this->parsePriorityList($routes);
    }

    public function testAllRoutesAsUser(): void
    {
        $this->setUpWithRole();
        /** @var LanguageAwareTreeRouteStack $router */
        $router = $this->getApplication()->getServiceManager()->get('router');
        /** @var Iterator $routes */
        $routes = $router->getRoutes();
        $this->parsePriorityList($routes);
    }

    public function testAllRoutesAsAdmin(): void
    {
        $this->setUpWithRole('admin');
        /** @var LanguageAwareTreeRouteStack $router */
        $router = $this->getApplication()->getServiceManager()->get('router');
        /** @var Iterator $routes */
        $routes = $router->getRoutes();
        $this->parsePriorityList($routes);
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    protected function parsePriorityList(Iterator $list): void
    {
        foreach ($list as $element) {
            if ($element instanceof Part) {
                $this->parsePart($element);
            } elseif ($element instanceof Literal) {
                $this->parseLiteral($element);
            } elseif ($element instanceof Segment) {
                $this->parseSegment($element);
            } elseif ($element instanceof Regex) {
                $this->parseRegex($element);
            } elseif ($element instanceof Method) {
                $this->parseMethod($element);
            } else {
                throw new RuntimeException(
                    sprintf(
                        'Unexpected type in parsePriorityList: %s',
                        $element::class,
                    ),
                );
            }
        }
    }

    protected function parsePart(Part $part): void
    {
        try {
            $this->parseSegment($part);
        } catch (RuntimeException) {
            // An exception is thrown if the route may not terminate.
        }

        $routes = $part->getRoutes();
        if (!($routes instanceof PriorityList)) {
            throw new RuntimeException(
                sprintf(
                    'Unexpected type in parsePart: %s',
                    $routes::class,
                ),
            );
        }

        $this->parsePriorityList($routes);
    }

    protected function parseSegment(Segment|Part $element): void
    {
        $params = $this->getParams();
        try {
            $url = $element->assemble($params);
            $this->parseUrl($url);
        } catch (InvalidArgumentException $exception) {
            $this->addWarning(
                // phpcs:ignore Generic.Files.LineLength.TooLong -- user-visible strings should not be split
                'Skipping one or multiple route segments/parts because required parameters could not be generated automatically.',
            );
            $this->addWarning($exception->getMessage());
            try {
                $this->addWarning(serialize($element));
            } catch (Throwable) {
                $this->addWarning('More details could not be provided through serialization.');
                // A part is not always serializable.
            }
        }
    }

    protected function parseLiteral(Literal $literal): void
    {
        $url = $literal->assemble();
        $this->parseUrl($url);
    }

    protected function parseRegex(Regex $regex): void
    {
        $url = $regex->assemble();
        $this->parseUrl($url);
    }

    protected function parseMethod(Method $method): void
    {
        // We can assemble all we want, but we will never get a testable route (so we do nothing).
    }

    protected function parseUrl(mixed $url): void
    {
        if (!is_string($url)) {
            throw new RuntimeException(
                sprintf(
                    'Unexpected type in parseUrl: %s',
                    $url::class,
                ),
            );
        }

        $this->testRoute($url);
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

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
     */
    protected function getParams(): array
    {
        $params = [];

        $params['id'] = 1;
        $params['appId'] = 1;
        $params['number'] = 1;
        $params['action'] = '';

        $params['lidnr'] = 8000;

        $params['user_type'] = 'member';
        $params['code'] = 'DS55VL8dCQuJ8Uc7nfMU4DQyT65BWmL5fJnLvgSutUjkDTCr';

        $params['organ'] = 1;
        $params['type'] = 'committee';
        $params['abbr'] = 'ABC';

        $params['category'] = 'vacancies';
        $params['companySlugName'] = 'asml';
        $params['packageId'] = 1;
        $params['jobId'] = 1;
        $params['jobLabelId'] = 1;
        $params['jobCategoryId'] = 1;
        $params['jobSlugName'] = 'gen_CS_offers';

        $params['album_type'] = 'album';
        $params['album_id'] = 1;
        $params['photo_id'] = 1;
        $params['signupList'] = 1;

        $params['regulation'] = 'key-policy';
        $params['filename'] = 'file.pdf';

        $params['code'] = 'rbIfZwWKyN7gavp00f4Ygs1ANuGDsL8v';

        $params['course'] = '2IABS0';
        $params['document'] = 1;

        $params['document_id'] = 42;

        return $params;
    }
}
