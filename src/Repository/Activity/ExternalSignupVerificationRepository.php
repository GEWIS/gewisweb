<?php

declare(strict_types=1);

namespace App\Repository\Activity;

use App\Entity\Activity\Enums\ExternalSignupVerificationPurpose;
use App\Entity\Activity\ExternalSignup;
use App\Entity\Activity\ExternalSignupVerification;
use App\Entity\Activity\SignupList;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ManagerRegistry;

use function array_map;

/**
 * @extends ServiceEntityRepository<ExternalSignupVerification>
 */
class ExternalSignupVerificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            ExternalSignupVerification::class,
        );
    }

    /**
     * Look up a token by its selector. The verifier must still be hash-compared against `getHashedToken()` with
     * `hash_equals`, and the purpose/expiry checked, before honouring it.
     */
    public function findBySelector(string $selector): ?ExternalSignupVerification
    {
        return $this->findOneBy(['selector' => $selector]);
    }

    /**
     * Whether the given external sign-up still has a pending double-opt-in (Verify) token, i.e. is unverified.
     */
    public function hasPendingVerification(ExternalSignup $externalSignup): bool
    {
        return null !== $this->findOneBy([
            'externalSignup' => $externalSignup,
            'purpose' => ExternalSignupVerificationPurpose::Verify,
        ]);
    }

    /**
     * The ids of external sign-ups on a list that are still unverified (have a live Verify token), so callers can hide
     * them from public lists, counts and admission.
     *
     * @return int[]
     */
    public function findPendingExternalSignupIdsForList(SignupList $signupList): array
    {
        $rows = $this->createQueryBuilder('v')
            ->select('IDENTITY(v.externalSignup) AS sid')
            ->innerJoin(
                'v.externalSignup',
                'es',
            )
            ->where('v.purpose = :purpose')
            ->andWhere('es.signupList = :list')
            ->setParameter(
                'purpose',
                ExternalSignupVerificationPurpose::Verify,
            )
            ->setParameter(
                'list',
                $signupList->getId(),
                Types::INTEGER,
            )
            ->getQuery()
            ->getScalarResult();

        return array_map(
            static fn (array $row): int => (int) $row['sid'],
            $rows,
        );
    }

    /**
     * The external sign-ups whose double-opt-in (Verify) token has expired without being confirmed; used by the prune
     * command to delete unconfirmed sign-ups.
     *
     * @return ExternalSignup[]
     */
    public function findExpiredUnverifiedSignups(): array
    {
        // Select the sign-ups directly (DISTINCT + fetch-join): a sign-up that ever held more than one expired Verify
        // token must be returned once, and this avoids a lazy-load query per row in the prune loop.
        /** @var ExternalSignup[] $signups */
        $signups = $this->createQueryBuilder('v')
            ->select('es')
            ->distinct()
            ->innerJoin(
                'v.externalSignup',
                'es',
            )
            ->where('v.purpose = :purpose')
            ->andWhere('v.expiresAt <= :now')
            ->setParameter(
                'purpose',
                ExternalSignupVerificationPurpose::Verify,
            )
            ->setParameter(
                'now',
                new DateTimeImmutable('now'),
                Types::DATETIME_IMMUTABLE,
            )
            ->getQuery()
            ->getResult();

        return $signups;
    }

    /**
     * Remove every token (Verify and Manage) for a sign-up, e.g. when it is withdrawn or pruned.
     */
    public function deleteAllForSignup(ExternalSignup $externalSignup): void
    {
        $this->createQueryBuilder('v')
            ->delete()
            ->where('v.externalSignup = :signup')
            ->setParameter(
                'signup',
                $externalSignup->getId(),
                Types::INTEGER,
            )
            ->getQuery()
            ->execute();
    }
}
