<?php

declare(strict_types=1);

namespace App\Repository\User;

use App\Entity\User\CompanyUser;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use Override;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

use function sprintf;
use function strtolower;
use function trim;

/**
 * @extends ServiceEntityRepository<CompanyUser>
 */
class CompanyUserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface, UserLoaderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            CompanyUser::class,
        );
    }

    #[Override]
    public function loadUserByIdentifier(string $identifier): ?CompanyUser
    {
        $qb = $this->createQueryBuilder('u');
        $qb->addSelect('c')
            ->join(
                'u.company',
                'c',
            )
            ->where('LOWER(c.representativeEmail) = :email')
            ->setParameter(
                'email',
                strtolower($identifier),
            );

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Admin overview paginator: every company user with their `Company` hydrated for display and search.
     *
     * @return Paginator<CompanyUser>
     */
    public function paginateForAdmin(
        string $search,
        string $sort,
        string $direction,
        int $page,
        int $pageSize,
    ): Paginator {
        $qb = $this->createQueryBuilder('u')
            ->join(
                'u.company',
                'c',
            )
            ->addSelect('c');

        $search = trim($search);
        if ('' !== $search) {
            $needle = '%' . strtolower($search) . '%';
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like(
                        'LOWER(c.name)',
                        ':needle',
                    ),
                    $qb->expr()->like(
                        'LOWER(c.representativeName)',
                        ':needle',
                    ),
                    $qb->expr()->like(
                        'LOWER(c.representativeEmail)',
                        ':needle',
                    ),
                ),
            )->setParameter(
                'needle',
                $needle,
            );
        }

        $orderField = match ($sort) {
            'name' => 'c.representativeName',
            'email' => 'c.representativeEmail',
            'mfa' => 'u.totpSecret',
            default => 'c.name',
        };
        $qb->orderBy(
            $orderField,
            'desc' === strtolower($direction) ? 'DESC' : 'ASC',
        );

        $qb->setFirstResult(($page - 1) * $pageSize)->setMaxResults($pageSize);

        return new Paginator($qb);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    #[Override]
    public function upgradePassword(
        PasswordAuthenticatedUserInterface $user,
        string $newHashedPassword,
    ): void {
        if (!$user instanceof CompanyUser) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }
}
