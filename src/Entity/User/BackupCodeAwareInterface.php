<?php

declare(strict_types=1);

namespace App\Entity\User;

/**
 * An account that can hold MFA backup codes. Implemented by {@see User} and {@see CompanyUser} through
 * {@see \App\Entity\User\Traits\BackupCodeAwareTrait}; it exists so {@see \App\Security\User\BackupCodeManager} can
 * work with the slots behind scheb's `object $user` contract.
 */
interface BackupCodeAwareInterface
{
    /**
     * Decoded backup-code slots, or `null` when MFA is disabled / no codes have been issued.
     *
     * @return array<array{code: string, used: bool}>|null
     */
    public function getBackupCodeSlots(): ?array;

    /**
     * @param array<array{code: string, used: bool}>|null $slots
     */
    public function setBackupCodeSlots(?array $slots): void;
}
