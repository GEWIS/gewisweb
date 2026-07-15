<?php

declare(strict_types=1);

namespace App\Repository\Photo;

use App\Entity\Photo\Tag;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository for the {@see Tag} single-table-inheritance root. Handles queries that span both tag subtypes; member- and
 * organ-specific queries live on {@see MemberTagRepository} and {@see OrganTagRepository}.
 *
 * @extends ServiceEntityRepository<Tag>
 */
class TagRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            Tag::class,
        );
    }

    /**
     * All tags (member and organ) on a photo.
     *
     * @return Tag[]
     */
    public function findByPhoto(int $photoId): array
    {
        return $this->findBy(['photo' => $photoId]);
    }
}
