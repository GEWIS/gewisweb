<?php

declare(strict_types=1);

namespace App\Repository\User;

use App\Entity\User\User;
use App\Entity\User\UserSettings;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserSettings>
 */
class UserSettingsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            UserSettings::class,
        );
    }

    /**
     * The settings row for the given user, creating (and persisting, but not flushing) a defaults row when none exists
     * yet. The caller flushes. Used by the write paths (settings form, cosmetics toggle).
     */
    public function getOrCreateForUser(User $user): UserSettings
    {
        $settings = $this->find($user->getLidnr());
        if (null === $settings) {
            $settings = new UserSettings($user);
            $this->getEntityManager()->persist($settings);
        }

        return $settings;
    }

    /**
     * Load the settings rows for a set of members, keyed by `lidnr`. Members without a row are simply absent from the
     * result (they take all-default settings). Used to evaluate the birthday panel without an N+1.
     *
     * @param int[] $lidnrs
     *
     * @return array<int, UserSettings>
     */
    public function findByLidnrs(array $lidnrs): array
    {
        if ([] === $lidnrs) {
            return [];
        }

        /** @var UserSettings[] $rows */
        $rows = $this->createQueryBuilder('s')
            ->where('s.user IN (:lidnrs)')
            ->setParameter(
                'lidnrs',
                $lidnrs,
            )
            ->getQuery()
            ->getResult();

        $byLidnr = [];
        foreach ($rows as $row) {
            $byLidnr[$row->getUser()->getLidnr()] = $row;
        }

        return $byLidnr;
    }
}
