<?php

declare(strict_types=1);

namespace App\Entity\Application\Enums;

use Override;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The app-level maintenance state. {@see ReadOnly} lets everyone keep reading but blocks writes for non-admins;
 * {@see Full} shows the maintenance page to non-admins. Admins bypass both.
 */
enum MaintenanceStatus: string implements TranslatableInterface
{
    case None = 'none';
    case ReadOnly = 'read-only';
    case Full = 'full';

    #[Override]
    public function trans(
        TranslatorInterface $translator,
        ?string $locale = null,
    ): string {
        return match ($this) {
            self::None => $translator->trans(
                'Off',
                locale: $locale,
            ),
            self::ReadOnly => $translator->trans(
                'Read-only',
                locale: $locale,
            ),
            self::Full => $translator->trans(
                'Full',
                locale: $locale,
            ),
        };
    }
}
