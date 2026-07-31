<?php

declare(strict_types=1);

namespace App\Security\User;

use Symfony\Component\HttpFoundation\Request;

use function mb_substr;

/**
 * Where a request came from, as the parts that name a device.
 *
 * Only ever a description, never an identity: nothing decides anything on the strength of it. The parts are returned
 * rather than a sentence, because the words joining them are translated wherever they are read.
 */
final readonly class RequestOrigin
{
    private const int MAX_LENGTH = 255;

    public function __construct(
        private UserAgentParser $userAgentParser,
    ) {
    }

    /**
     * @return array{browser?: string, system?: string, address?: string}
     */
    public function describe(Request $request): array
    {
        $meta = $this->userAgentParser->parseRequest($request);

        return self::parts(
            $meta['browser'],
            $meta['operatingSystem'],
            $request->getClientIp(),
        );
    }

    /**
     * @return array{browser?: string, system?: string, address?: string}
     */
    public static function parts(
        ?string $browser,
        ?string $system,
        ?string $address,
    ): array {
        $origin = [];

        foreach (
            [
                'browser' => $browser,
                'system' => $system,
                'address' => $address,
            ] as $key => $value
        ) {
            if (
                null === $value
                || '' === $value
            ) {
                continue;
            }

            $origin[$key] = mb_substr(
                $value,
                0,
                self::MAX_LENGTH,
            );
        }

        return $origin;
    }
}
