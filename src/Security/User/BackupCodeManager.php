<?php

declare(strict_types=1);

namespace App\Security\User;

use Doctrine\ORM\EntityManagerInterface;
use Override;
use Scheb\TwoFactorBundle\Security\TwoFactor\Backup\BackupCodeManagerInterface;

use function array_values;
use function bin2hex;
use function hash_equals;
use function random_bytes;

/**
 * Drop-in replacement for scheb's default backup-code manager.
 *
 * Backup codes are stored plaintext inside an array payload that is itself encrypted at rest by DoctrineEncryptBundle
 * (same primitive used for {@see \App\Entity\User\User::$totpSecret}). The `code` value can therefore be compared
 * directly with `hash_equals()`. However, input format/length MUST be enforced upstream. Such as by the `Regex`
 * constraint on {@see \App\Form\User\SudoConfirmFormType} for the sudo flow, and by the HTML5 `pattern` attribute on
 * the MFA challenge template for the login flow. The manager therefore only ever sees well-shaped input under normal
 * use; a length mismatch from a bypassed validator simply causes `hash_equals` to return false for every slot.
 *
 * Each slot carries a `used` flag. Consuming a code flips the flag but keeps the slot in place, so the verification
 * path always performs the same number of comparisons. As such, the wall-clock time of {@see isBackupCode()} does not
 * reveal which slot matched or whether any slot was already spent. From the caller's perspective a spent code is
 * rejected like any other invalid input; no UI surface tells the requester that their code was already used.
 *
 * Plaintext is generated only at enrollment / regeneration time and returned to the controller for one-shot display;
 * the same plaintext continues to live (encrypted) in the DB so subsequent verifications can match against it.
 */
final class BackupCodeManager implements BackupCodeManagerInterface
{
    public const int DEFAULT_COUNT = 8;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Override]
    public function isBackupCode(
        object $user,
        string $code,
    ): bool {
        $matched = false;

        foreach ($user->getBackupCodeSlots() ?? [] as $entry) {
            // `hash_equals` runs first and unconditionally; the `&& !used` then makes spent codes count as a miss
            // without affecting timing. Same shape as the previous argon2-based implementation.
            $matched = (hash_equals(
                $entry['code'],
                $code,
            ) && !$entry['used']) || $matched;
        }

        return $matched;
    }

    #[Override]
    public function invalidateBackupCode(
        object $user,
        string $code,
    ): void {
        $entries = $user->getBackupCodeSlots() ?? [];
        $matchedIndex = null;

        foreach ($entries as $i => $entry) {
            // Security is not so important at this stage, so skip directly over any used slots to save time.
            if ($entry['used']) {
                continue;
            }

            if (
                hash_equals(
                    $entry['code'],
                    $code,
                )
            ) {
                $matchedIndex = $i;
                break;
            }
        }

        if (null === $matchedIndex) {
            return;
        }

        $entries[$matchedIndex]['used'] = true;
        $user->setBackupCodeSlots(array_values($entries));
        $this->entityManager->flush();
    }

    /**
     * Generate {@see DEFAULT_COUNT} fresh codes, persist them on the user, and return the plaintext set so the caller
     * can show it once.
     *
     * @return string[]
     */
    public function generateAndStore(
        object $user,
        int $count = self::DEFAULT_COUNT,
    ): array {
        $plaintext = [];
        $entries = [];

        for ($i = 0; $i < $count; $i++) {
            // 16 hex chars = 64 bits of entropy.
            $code = bin2hex(random_bytes(8));
            $plaintext[] = $code;
            $entries[] = [
                'code' => $code,
                'used' => false,
            ];
        }

        $user->setBackupCodeSlots($entries);
        $this->entityManager->flush();

        return $plaintext;
    }
}
