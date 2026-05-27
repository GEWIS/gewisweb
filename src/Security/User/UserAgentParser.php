<?php

declare(strict_types=1);

namespace App\Security\User;

use App\Entity\User\Enums\DeviceTypes;
use DeviceDetector\DeviceDetector as MatomoDetector;
use DeviceDetector\Parser\Device\AbstractDeviceParser;
use DeviceDetector\Yaml\Symfony as SymfonyYamlParser;

use function explode;
use function is_array;
use function trim;

/**
 * Thin wrapper around Matomo's `DeviceDetector` that yields the three pieces we persist on
 * {@see \App\Entity\User\Session}: the semantic device type, the browser name + major version (joined), and the OS name
 * + major version (joined).
 */
final readonly class UserAgentParser
{
    /**
     * @return array{type: DeviceTypes, browser: ?string, operatingSystem: ?string}
     */
    public function parse(string $userAgent): array
    {
        if ('' === trim($userAgent)) {
            return [
                'type' => DeviceTypes::Unknown,
                'browser' => null,
                'operatingSystem' => null,
            ];
        }

        AbstractDeviceParser::setVersionTruncation(AbstractDeviceParser::VERSION_TRUNCATION_NONE);

        $dd = new MatomoDetector($userAgent);
        $dd->setYamlParser(new SymfonyYamlParser());
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
            $version = isset($os['version'])
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
