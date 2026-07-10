<?php

declare(strict_types=1);

namespace App\Repository\Photo;

use App\Entity\Photo\OrganTag;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository for {@see OrganTag}s, the organ-linking tag subtype (GH-1991).
 *
 * @extends ServiceEntityRepository<OrganTag>
 */
class OrganTagRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            OrganTag::class,
        );
    }

    /**
     * The tag of a given organ on a given photo, if any.
     */
    public function findTag(
        int $photoId,
        int $organId,
    ): ?OrganTag {
        return $this->findOneBy(
            [
                'photo' => $photoId,
                'organ' => $organId,
            ],
        );
    }

    /**
     * The organ tags on a photo with their organ fetched in the same query, so the viewer overlay can read each tagged
     * organ's name without a per-tag lazy load.
     *
     * @return OrganTag[]
     */
    public function findByPhotoWithOrgan(int $photoId): array
    {
        return $this->createQueryBuilder('t')
            ->addSelect('o')
            ->join(
                't.organ',
                'o',
            )
            ->where('t.photo = :photo')
            ->setParameter(
                'photo',
                $photoId,
            )
            ->getQuery()
            ->getResult();
    }
}
