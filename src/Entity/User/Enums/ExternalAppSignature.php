<?php

declare(strict_types=1);

namespace App\Entity\User\Enums;

use Symfony\Component\Translation\TranslatableMessage;

/**
 * How an external application's token is signed, in order of preference. Modern applications use one of the
 * association's asymmetric keys, which they verify through the JWKS endpoint; the legacy shared-secret profile signs
 * with a per-application secret. New registrations should pick the strongest algorithm the application supports,
 * starting from EdDSA.
 */
enum ExternalAppSignature: string
{
    case EdDSA = 'EdDSA';
    case ES512 = 'ES512';
    case PS512 = 'PS512';
    case RS512 = 'RS512';
    case HS512 = 'HS512';

    /**
     * Whether the profile signs with a per-application shared secret rather than one of the association's keys.
     */
    public function usesSharedSecret(): bool
    {
        return self::HS512 === $this;
    }

    public function label(): TranslatableMessage
    {
        return match ($this) {
            self::EdDSA => new TranslatableMessage('EdDSA (recommended)'),
            self::ES512 => new TranslatableMessage('ES512'),
            self::PS512 => new TranslatableMessage('PS512'),
            self::RS512 => new TranslatableMessage('RS512 (legacy, avoid for new applications)'),
            self::HS512 => new TranslatableMessage('HS512 (shared secret, legacy)'),
        };
    }
}
