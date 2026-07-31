<?php

declare(strict_types=1);

namespace App\Tests\Security\User;

use App\Entity\User\Enums\DeviceTypes;
use App\Security\User\UserAgentParser;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class UserAgentParserTest extends TestCase
{
    private const string CHROME_ON_WINDOWS = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 '
        . '(KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36';

    /**
     * Windows 11 reports itself as `Windows NT 10.0` and always will, so a version read from the user agent would tell
     * somebody on Windows 11 that they are on Windows 10. The browser version in the user agent is accurate and stays.
     */
    public function testOnlyTheSystemVersionIsWithheldWithoutAHint(): void
    {
        $parsed = new UserAgentParser()->parse(self::CHROME_ON_WINDOWS);

        self::assertSame(
            'Windows',
            $parsed['operatingSystem'],
        );
        self::assertSame(
            'Chrome 124',
            $parsed['browser'],
        );
    }

    public function testAStatedVersionIsShown(): void
    {
        self::assertSame(
            'Chrome 124',
            $this->parseWithHints('15.0.0')['browser'],
        );
    }

    public function testTheClientHintSeparatesThem(): void
    {
        self::assertSame(
            'Windows 11',
            $this->parseWithHints('15.0.0')['operatingSystem'],
        );
        self::assertSame(
            'Windows 10',
            $this->parseWithHints('10.0.0')['operatingSystem'],
        );
    }

    public function testAHintlessRequestStillNamesTheBrowserAndSystem(): void
    {
        $request = new Request();
        $request->headers->set(
            'User-Agent',
            self::CHROME_ON_WINDOWS,
        );

        $parsed = new UserAgentParser()->parseRequest($request);

        self::assertSame(
            'Windows',
            $parsed['operatingSystem'],
        );
        self::assertSame(
            'Chrome 124',
            $parsed['browser'],
        );
    }

    /**
     * Only `sec-ch-ua*` headers are hints; everything else on the request is none of the detector's business.
     */
    public function testOnlyClientHintHeadersArePassedOn(): void
    {
        $request = new Request();
        $request->headers->set(
            'Cookie',
            'GWS_SESSION=secret',
        );
        $request->headers->set(
            'Sec-CH-UA-Platform',
            '"Windows"',
        );

        self::assertSame(
            ['sec-ch-ua-platform' => '"Windows"'],
            UserAgentParser::clientHints($request),
        );
    }

    public function testAnEmptyRequestIsUnknownRatherThanAnError(): void
    {
        $parsed = new UserAgentParser()->parseRequest(new Request());

        self::assertSame(
            DeviceTypes::Unknown,
            $parsed['type'],
        );
        self::assertNull($parsed['browser']);
        self::assertNull($parsed['operatingSystem']);
    }

    /**
     * @return array{type: DeviceTypes, browser: ?string, operatingSystem: ?string}
     */
    private function parseWithHints(string $platformVersion): array
    {
        $request = new Request();
        $request->headers->set(
            'User-Agent',
            self::CHROME_ON_WINDOWS,
        );
        $request->headers->set(
            'Sec-CH-UA',
            '"Chromium";v="124", "Google Chrome";v="124"',
        );
        $request->headers->set(
            'Sec-CH-UA-Platform',
            '"Windows"',
        );
        $request->headers->set(
            'Sec-CH-UA-Platform-Version',
            '"' . $platformVersion . '"',
        );

        return new UserAgentParser()->parseRequest($request);
    }
}
