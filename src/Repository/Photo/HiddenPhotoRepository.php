<?php

declare(strict_types=1);

namespace App\Repository\Photo;

use App\Entity\Decision\Member;
use App\Entity\Photo\HiddenPhoto;
use App\Entity\Photo\Photo;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

use function intval;

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
            $ids[intval($row['photoId'])] = true;
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

    /**
     * The member's hidden-photo rows among the given photo ids, in one query instead of a
     * {@see self::findByMemberAndPhoto} per photo.
     *
     * @param int[] $photoIds
     *
     * @return HiddenPhoto[]
     */
    public function findByMemberAndPhotos(
        Member $member,
        array $photoIds,
    ): array {
        if ([] === $photoIds) {
            return [];
        }

        return $this->createQueryBuilder('h')
            ->where('h.member = :member')
            ->andWhere('h.photo IN (:photos)')
            ->setParameter(
                'member',
                $member->getLidnr(),
            )
            ->setParameter(
                'photos',
                $photoIds,
            )
            ->getQuery()
            ->getResult();
    }
}
