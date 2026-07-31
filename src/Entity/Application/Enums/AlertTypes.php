<?php

declare(strict_types=1);

namespace App\Entity\Application\Enums;

use Override;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

enum AlertTypes: string implements TranslatableInterface
{
    case Success = 'success';
    case Danger = 'danger';
    case Warning = 'warning';
    case Info = 'info';

    #[Override]
    public function trans(
        TranslatorInterface $translator,
        ?string $locale = null,
    ): string {
        return match ($this) {
            self::Success => $translator->trans(
                'Success',
                locale: $locale,
            ),
            self::Danger => $translator->trans(
                'Danger',
                locale: $locale,
            ),
            self::Warning => $translator->trans(
                'Warning',
                locale: $locale,
            ),
            self::Info => $translator->trans(
                'Information',
                locale: $locale,
            ),
        };
    }
}
