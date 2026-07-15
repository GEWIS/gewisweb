<?php

declare(strict_types=1);

namespace App\Repository\Photo;

use App\Entity\Decision\Member;
use App\Entity\Photo\HiddenPhoto;
use App\Entity\Photo\Photo;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<HiddenPhoto>
 */
class HiddenPhotoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            HiddenPhoto::class,
        );
    }

    /**
     * The ids of the photos a member has hidden, as a set for O(1) lookup while filtering their photo page.
     *
     * @return array<int, true>
     */
    public function getHiddenPhotoIds(Member $member): array
    {
        $ids = [];
        foreach (
            $this->createQueryBuilder('h')
                ->select('IDENTITY(h.photo) AS photoId')
                ->where('h.member = :member')
                ->setParameter(
                    'member',
                    $member->getLidnr(),
                )
                ->getQuery()
                ->getScalarResult() as $row
        ) {
            $ids[(int) $row['photoId']] = true;
        }

        return $ids;
    }

    public function findByMemberAndPhoto(
        Member $member,
        Photo $photo,
    ): ?HiddenPhoto {
        return $this->findOneBy(
            [
                'member' => $member,
                'photo' => $photo,
            ],
        );
    }
}
