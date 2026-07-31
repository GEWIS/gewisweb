<?php

declare(strict_types=1);

namespace App\Service\Application;

use App\Entity\Application\Enums\Languages;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatorInterface;

use function sprintf;
use function trim;

/**
 * Names a device in a way somebody can recognise: "Chrome 124 on Windows (192.0.2.1)".
 *
 * Whatever was not recognised is left out rather than shown empty, and the joining word is translated here rather than
 * frozen into the text, which is why notifications keep the parts instead of a finished sentence.
 */
final readonly class DeviceDescription
{
    public function __construct(
        private TranslatorInterface $translator,
    ) {
    }

    public function render(
        ?string $browser,
        ?string $system,
        ?string $address = null,
        ?Languages $language = null,
    ): string {
        $locale = ($language ?? Languages::current())->getLangParam();

        $browser = self::clean($browser);
        $system = self::clean($system);
        $address = self::clean($address);

        $device = match (true) {
            null !== $browser && null !== $system => new TranslatableMessage(
                '%browser% on %system%',
                [
                    '%browser%' => $browser,
                    '%system%' => $system,
                ],
            )->trans(
                $this->translator,
                $locale,
            ),
            null !== $browser => $browser,
            null !== $system => $system,
            default => null,
        };

        if (null === $device) {
            return $address ?? $this->translator->trans(
                'Unknown device',
                locale: $locale,
            );
        }

        if (null === $address) {
            return $device;
        }

        return sprintf(
            '%s (%s)',
            $device,
            $address,
        );
    }

    private static function clean(?string $part): ?string
    {
        if (null === $part) {
            return null;
        }

        $part = trim($part);

        return '' === $part
            ? null
            : $part;
    }
}
