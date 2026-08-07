<?php

declare(strict_types=1);

namespace App\Tests\Service\Education;

use App\Service\Education\CampusNetworkChecker;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * The half of the download rule that is not about being logged in: anyone on the TU/e campus network may fetch course
 * material. It is the only place in the application where a client address decides anything, so what counts as being on
 * campus is pinned here rather than left to a subnet list nobody reads.
 */
final class CampusNetworkCheckerTest extends TestCase
{
    private const array RANGES = [
        '131.155.0.0/16',
        '100.64.0.0/10',
    ];

    public function testAnAddressInsideAConfiguredRangeIsOnCampus(): void
    {
        self::assertTrue($this->checker()->matches('131.155.10.7'));
        self::assertTrue($this->checker()->matches('100.75.0.1'));
    }

    public function testAnAddressOutsideEveryRangeIsNot(): void
    {
        self::assertFalse($this->checker()->matches('8.8.8.8'));
        // One octet outside the /16, which is exactly the kind of thing a hand-rolled netmask gets wrong.
        self::assertFalse($this->checker()->matches('131.156.10.7'));
        self::assertFalse($this->checker()->matches('100.63.255.255'));
    }

    public function testNothingIsOnCampusWithoutAnAddress(): void
    {
        self::assertFalse($this->checker()->matches(null));
    }

    public function testGarbageIsNotOnCampus(): void
    {
        self::assertFalse($this->checker()->matches('not an address'));
        self::assertFalse($this->checker()->matches(''));
    }

    /**
     * An unconfigured range list must not accidentally let everyone in.
     */
    public function testNoConfiguredRangesMeansNobodyIsOnCampus(): void
    {
        $checker = new CampusNetworkChecker(
            new RequestStack(),
            [],
        );

        self::assertFalse($checker->matches('131.155.10.7'));
    }

    public function testTheCurrentRequestDecidesWhenThereIsOne(): void
    {
        $stack = new RequestStack();
        $stack->push(Request::create(
            '/education',
            server: ['REMOTE_ADDR' => '131.155.10.7'],
        ));

        self::assertTrue(new CampusNetworkChecker($stack, self::RANGES)->isOnCampus());
    }

    /**
     * A worker has no request, and must not be treated as being on campus because of it.
     */
    public function testWithoutARequestNothingIsOnCampus(): void
    {
        self::assertFalse($this->checker()->isOnCampus());
    }

    private function checker(): CampusNetworkChecker
    {
        return new CampusNetworkChecker(
            new RequestStack(),
            self::RANGES,
        );
    }
}
