<?php

declare(strict_types=1);

namespace App\Repository\User;

use App\Entity\Decision\Member;
use App\Entity\User\User;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ManagerRegistry;
use Override;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

use function is_numeric;
use function preg_match;
use function sprintf;
use function strtolower;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface, UserLoaderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            User::class,
        );
    }

    #[Override]
    public function loadUserByIdentifier(string $identifier): ?User
    {
        $qb = $this->createQueryBuilder('u');
        $qb->addSelect(
            'm',
            'r',
        )
            ->leftJoin(
                'u.roles',
                'r',
            )
            ->join(
                'u.member',
                'm',
            );

        // QOL: active members are used to logging in with `m{lidnr}`, so allow that as well.
        if (
            preg_match(
                '/^m(\d+)$/',
                $identifier,
                $matches,
            )
        ) {
            $identifier = $matches[1]; // do not cast to int, otherwise extra an if-statement is needed for lowercasing
        }

        // Depending on how the user is logging in, add the correct WHERE-clause.
        if (is_numeric($identifier)) {
            $qb->where('u.lidnr = :login');
        } else {
            $qb->where('LOWER(m.email) = :login');
        }

        $qb->setParameter(
            'login',
            strtolower($identifier),
        );

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    #[Override]
    public function upgradePassword(
        PasswordAuthenticatedUserInterface $user,
        string $newHashedPassword,
    ): void {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    /**
     * Bulk-load users by lidnr, fetch-joining their roles in the same query. Used by the admin users overview to avoid
     * the N+1 that the EAGER `User::$roles` mapping would otherwise produce when hydrating one user at a time.
     *
     * @param list<int> $lidnrs
     *
     * @return list<User>
     */
    public function findByLidnrsWithRoles(array $lidnrs): array
    {
        if ([] === $lidnrs) {
            return [];
        }

        /** @var list<User> $users */
        $users = $this->createQueryBuilder('u')
            ->leftJoin(
                'u.roles',
                'r',
            )
            ->addSelect('r')
            ->where('u.lidnr IN (:lidnrs)')
            ->setParameter(
                'lidnrs',
                $lidnrs,
            )
            ->getQuery()
            ->getResult();

        return $users;
    }

    /**
     * Used for password resets, does not include members who are hidden, expired, and/or deleted. These requirements
     * are also used during the login process.
     */
    public function findForReset(
        string $email,
        int $lidnr,
    ): ?User {
        $qb = $this->createQueryBuilder('u');
        $qb->innerJoin(
            Member::class,
            'm',
            'ON',
            'u.lidnr = m.lidnr',
        )
            ->where('u.lidnr = :lidnr')
            ->andWhere('LOWER(m.email) = :email')
            ->andWhere('m.deleted = :false')
            ->andWhere('m.hidden = :false')
            ->andWhere('m.expiration > :now');

        $qb->setParameter(
            'lidnr',
            $lidnr,
        )
            ->setParameter(
                'email',
                strtolower($email),
            )
            ->setParameter(
                'false',
                false,
            )
            ->setParameter(
                'now',
                new DateTime('now'),
                Types::DATETIME_MUTABLE,
            );

        return $qb->getQuery()->getOneOrNullResult();
    }
}
