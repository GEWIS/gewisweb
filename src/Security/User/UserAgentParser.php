<?php

declare(strict_types=1);

namespace App\Security\User;

use App\Entity\User\Enums\DeviceTypes;
use DeviceDetector\ClientHints;
use DeviceDetector\DeviceDetector as MatomoDetector;
use DeviceDetector\Parser\Device\AbstractDeviceParser;
use DeviceDetector\Yaml\Symfony as SymfonyYamlParser;
use Symfony\Component\HttpFoundation\Request;

use function explode;
use function is_array;
use function str_starts_with;
use function trim;

/**
 * Thin wrapper around Matomo's `DeviceDetector` that yields the three pieces we persist on
 * {@see \App\Entity\User\Session}: the semantic device type, the browser name + major version (joined), and the OS name
 * + major version (joined).
 *
 * Client hints are read where the browser sends them, because the user agent alone cannot tell Windows 11 from Windows
 * 10: both still say `Windows NT 10.0`, and only `Sec-CH-UA-Platform-Version` separates them. The high-entropy hints
 * are requested by {@see \App\EventListener\Application\ClientHintsListener} and only ever arrive over a secure
 * context and from browsers that implement them, so the user agent remains the fallback rather than the exception.
 */
final readonly class UserAgentParser
{
    /**
     * Whether the browser actually sent this hint, as opposed to it being absent or empty.
     *
     * @param array<string, string> $clientHints
     */
    private static function stated(
        array $clientHints,
        string $hint,
    ): bool {
        return '' !== trim(
            $clientHints[$hint] ?? '',
            '" ',
        );
    }

    /**
     * @return array{type: DeviceTypes, browser: ?string, operatingSystem: ?string}
     */
    public function parseRequest(Request $request): array
    {
        return $this->parse(
            $request->headers->get(
                'User-Agent',
                '',
            ),
            self::clientHints($request),
        );
    }

    /**
     * The client hints a request carries, flattened to the single values Matomo expects.
     *
     * @return array<string, string>
     */
    public static function clientHints(Request $request): array
    {
        $hints = [];
        foreach ($request->headers->all() as $name => $values) {
            if (
                !str_starts_with(
                    $name,
                    'sec-ch-ua',
                )
            ) {
                continue;
            }

            $hints[$name] = $values[0] ?? '';
        }

        return $hints;
    }

    /**
     * The operating system version is only reported where the browser stated it in a client hint. `Windows NT 10.0`
     * covers both Windows 10 and Windows 11, so a version read from the user agent would show somebody on Windows 11 a
     * device they do not recognise. The browser version is taken from the user agent as before, which reports it
     * accurately.
     *
     * @param array<string, string> $clientHints
     *
     * @return array{type: DeviceTypes, browser: ?string, operatingSystem: ?string}
     */
    public function parse(
        string $userAgent,
        array $clientHints = [],
    ): array {
        if (
            '' === trim($userAgent)
            && [] === $clientHints
        ) {
            return [
                'type' => DeviceTypes::Unknown,
                'browser' => null,
                'operatingSystem' => null,
            ];
        }

        AbstractDeviceParser::setVersionTruncation(AbstractDeviceParser::VERSION_TRUNCATION_NONE);

        $dd = new MatomoDetector($userAgent);
        $dd->setYamlParser(new SymfonyYamlParser());

        if ([] !== $clientHints) {
            $dd->setClientHints(ClientHints::factory($clientHints));
        }

        $dd->parse();

        if ($dd->isBot()) {
            $bot = $dd->getBot();
            $name = is_array($bot)
                ? ($bot['name'] ?? 'Unknown bot')
                : 'Unknown bot';

            // Bots do not have an OS; the bot name lives in `browser` so the template's browser / OS / fallback chain
            // stays uniform.
            return [
                'type' => DeviceTypes::Bot,
                'browser' => $name,
                'operatingSystem' => null,
            ];
        }

        $client = $dd->getClient();
        $os = $dd->getOs();

        $osVersionStated = self::stated(
            $clientHints,
            'sec-ch-ua-platform-version',
        );

        $browser = null;
        if (
            is_array($client)
            && isset($client['name'])
            && '' !== $client['name']
        ) {
            $version = isset($client['version'])
                ? explode(
                    '.',
                    $client['version'],
                    2,
                )[0]
                : '';
            $browser = '' !== $version && 'UNK' !== $version
                ? $client['name'] . ' ' . $version
                : $client['name'];
        }

        $osStr = null;
        if (
            is_array($os)
            && isset($os['name'])
            && '' !== $os['name']
        ) {
            $version = $osVersionStated && isset($os['version'])
                ? explode(
                    '.',
                    $os['version'],
                    2,
                )[0]
                : '';
            $osStr = '' !== $version && 'UNK' !== $version
                ? $os['name'] . ' ' . $version
                : $os['name'];
        }

        return [
            'type' => DeviceTypes::fromMatomoName($dd->getDeviceName()),
            'browser' => $browser,
            'operatingSystem' => $osStr,
        ];
    }
}
