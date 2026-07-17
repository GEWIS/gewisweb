<?php

declare(strict_types=1);

namespace App\Entity\Decision\Enums;

use Symfony\Component\Translation\TranslatableMessage;

/**
 * Enum for the different membership types as defined in the articles of association.
 */
enum MembershipTypes: string
{
    case Ordinary = 'ordinary';
    case External = 'external';
    case Graduate = 'graduate';
    case Honorary = 'honorary';

    public function label(): TranslatableMessage
    {
        return match ($this) {
            self::Ordinary => new TranslatableMessage('Ordinary'),
            self::External => new TranslatableMessage('External'),
            self::Graduate => new TranslatableMessage('Graduate'),
            self::Honorary => new TranslatableMessage('Honorary'),
        };
    }

    /**
     * Whether this type is a member within the meaning of the law, with the associated rights (voting and the like).
     * Ordinary, external, and honorary members are; a graduate only holds graduate status. That distinction is why the
     * association tracks both a membership end date and an expiration.
     */
    public function isStatutoryMember(): bool
    {
        return self::Graduate !== $this;
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Ordinary => 'badge-gewis-primary',
            self::External => 'badge-success',
            self::Graduate => 'badge-info',
            self::Honorary => 'badge-warning',
        };
    }
}
