<?php

declare(strict_types=1);

namespace App\Tests\Security\User;

use App\Security\User\RequestOrigin;
use App\Security\User\UserAgentParser;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

use function str_repeat;
use function strlen;

final class RequestOriginTest extends TestCase
{
    private const string CHROME_ON_WINDOWS = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 '
        . '(KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36';

    /**
     * Without a client hint the system version is left off rather than guessed at; the browser version is accurate in
     * the user agent and stays.
     */
    public function testTheBrowserSystemAndAddressAreEachRecorded(): void
    {
        self::assertSame(
            [
                'browser' => 'Chrome 124',
                'system' => 'Windows',
                'address' => '192.0.2.1',
            ],
            $this->describe(
                self::CHROME_ON_WINDOWS,
                '192.0.2.1',
            ),
        );
    }

    /**
     * Anything that was not recognised is left out rather than recorded empty, so whoever reads it is not shown a gap.
     */
    public function testWhatIsNotRecognisedIsLeftOut(): void
    {
        self::assertSame(
            ['address' => '192.0.2.1'],
            $this->describe(
                '',
                '192.0.2.1',
            ),
        );
    }

    public function testAnUnrecognisedUserAgentRecordsNothingAboutTheDevice(): void
    {
        self::assertSame(
            [],
            $this->describe(
                'some-internal-client/1.0',
                null,
            ),
        );
    }

    public function testARequestThatSaysNothingRecordsNothing(): void
    {
        self::assertSame(
            [],
            $this->describe(
                '',
                null,
            ),
        );
    }

    /**
     * Each part is frozen onto a notification, whose column is bounded.
     */
    public function testAnAbsurdValueCannotOverflowTheColumn(): void
    {
        $parts = RequestOrigin::parts(
            str_repeat(
                'x',
                4096,
            ),
            null,
            null,
        );

        self::assertSame(
            255,
            strlen($parts['browser'] ?? ''),
        );
    }

    /**
     * @return array{browser?: string, system?: string, address?: string}
     */
    private function describe(
        string $userAgent,
        ?string $clientIp,
    ): array {
        $server = [];

        if (null !== $clientIp) {
            $server['REMOTE_ADDR'] = $clientIp;
        }

        $request = new Request(server: $server);
        $request->headers->set(
            'User-Agent',
            $userAgent,
        );

        return new RequestOrigin(new UserAgentParser())->describe($request);
    }
}
