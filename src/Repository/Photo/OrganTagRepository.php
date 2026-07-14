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
     * All organ tags on a photo.
     *
     * @return OrganTag[]
     */
    public function getTagsByPhoto(int $photoId): array
    {
        return $this->findBy(['photo' => $photoId]);
    }
}
