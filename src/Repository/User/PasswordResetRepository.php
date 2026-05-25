<?php

declare(strict_types=1);

namespace App\Repository\User;

use App\Entity\Decision\Member;
use App\Entity\User\CompanyUser;
use App\Entity\User\PasswordReset;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PasswordReset>
 */
class PasswordResetRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            PasswordReset::class,
        );
    }

    /**
     * Return the most recently created PasswordReset for a Member (if any).
     */
    public function findForMember(Member $member): ?PasswordReset
    {
        return $this->findOneBy(
            ['member' => $member],
            ['id' => 'DESC'],
        );
    }

    /**
     * Return the most recently created PasswordReset for a CompanyUser (if any).
     */
    public function findForCompanyUser(CompanyUser $companyUser): ?PasswordReset
    {
        return $this->findOneBy(
            ['companyUser' => $companyUser],
            ['id' => 'DESC'],
        );
    }

    /**
     * Look up a PasswordReset by its selector. The verifier must still be hash-compared against `getHashedToken()`
     * with `hash_equals` to confirm the token is genuine.
     */
    public function findBySelector(string $selector): ?PasswordReset
    {
        return $this->findOneBy(['selector' => $selector]);
    }

    /**
     * Look up a PasswordReset by its ephemeral temp hash (set in stage 1, consumed in stage 2). Caller must still
     * check `isTempHashExpired()` before honoring the result.
     */
    public function findByTempHash(string $tempHash): ?PasswordReset
    {
        return $this->findOneBy(['tempHash' => $tempHash]);
    }

    /**
     * Invalidate every outstanding reset for a Member, enforcing single-use of the reset link.
     */
    public function deleteAllForMember(Member $member): void
    {
        $this->createQueryBuilder('p')
            ->delete()
            ->where('p.member = :member')
            ->setParameter(
                'member',
                $member,
            )
            ->getQuery()
            ->execute();
    }

    /**
     * Invalidate every outstanding reset for a CompanyUser, enforcing single-use of the reset link.
     */
    public function deleteAllForCompanyUser(CompanyUser $companyUser): void
    {
        $this->createQueryBuilder('p')
            ->delete()
            ->where('p.companyUser = :companyUser')
            ->setParameter(
                'companyUser',
                $companyUser,
            )
            ->getQuery()
            ->execute();
    }
}
