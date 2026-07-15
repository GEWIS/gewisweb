<?php

declare(strict_types=1);

namespace App\Repository\Decision;

use App\Entity\Decision\OrganInformation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

use function addcslashes;

/**
 * @extends ServiceEntityRepository<OrganInformation>
 */
class OrganInformationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            OrganInformation::class,
        );
    }

    /**
     * The organ information whose cover or thumbnail path ends with the given filename, used to resolve a legacy
     * `/data/{2ch}/{file}` URL onto the migrated organ image (organ images re-root that same filename).
     */
    public function findOneByImageBasename(string $basename): ?OrganInformation
    {
        $suffix = '%/' . addcslashes(
            $basename,
            '%_',
        );

        return $this->createQueryBuilder('organ')
            ->where('organ.coverPath LIKE :suffix')
            ->orWhere('organ.thumbnailPath LIKE :suffix')
            ->setParameter(
                'suffix',
                $suffix,
            )
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
