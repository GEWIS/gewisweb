<?php

declare(strict_types=1);

namespace App\Repository\Decision;

use App\Entity\Decision\OrganInformationRevision;
use App\Repository\Application\FindsRevisionsForReviewTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

use function addcslashes;
use function intval;

/**
 * @extends ServiceEntityRepository<OrganInformationRevision>
 */
class OrganInformationRevisionRepository extends ServiceEntityRepository
{
    use FindsRevisionsForReviewTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            OrganInformationRevision::class,
        );
    }

    /**
     * The pages waiting on the board, oldest first. The queue names each one by its body, so the organ comes along.
     *
     * @return OrganInformationRevision[]
     */
    public function findForReview(): array
    {
        $builder = $this->createQueryBuilder('r')
            ->addSelect(
                'i',
                'o',
                'a',
            )
            ->join(
                'r.organInformation',
                'i',
            )
            ->join(
                'i.organ',
                'o',
            )
            ->leftJoin(
                'r.author',
                'a',
            );

        $this->whereAwaitingReview($builder);
        $this->orderOldestFirst($builder);

        return $builder->getQuery()
            ->getResult();
    }

    /**
     * The revision holding an image whose stored path ends with the given filename, used to resolve a legacy
     * `/data/{2ch}/{file}` URL onto the migrated organ image (organ images re-root that same filename). Any revision
     * counts: an old bookmark points at whatever was live when it was made.
     */
    public function findOneByImageBasename(string $basename): ?OrganInformationRevision
    {
        $suffix = '%/' . addcslashes(
            $basename,
            '%_',
        );

        return $this->createQueryBuilder('r')
            ->where('r.bannerPath LIKE :suffix')
            ->orWhere('r.logoPath LIKE :suffix')
            ->orWhere('r.bannerSource LIKE :suffix')
            ->orWhere('r.logoSource LIKE :suffix')
            ->setParameter(
                'suffix',
                $suffix,
            )
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * How many pages are waiting on the board, for the overviews that only say so rather than list them.
     */
    public function countForReview(): int
    {
        $builder = $this->createQueryBuilder('r')
            ->select('COUNT(r.id)');

        $this->whereAwaitingReview($builder);

        return intval(
            $builder->getQuery()
                ->getSingleScalarResult(),
        );
    }
}
