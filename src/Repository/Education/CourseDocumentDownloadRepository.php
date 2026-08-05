<?php

declare(strict_types=1);

namespace App\Repository\Education;

use App\Entity\Education\CourseDocumentDownload;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<CourseDocumentDownload>
 */
class CourseDocumentDownloadRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            CourseDocumentDownload::class,
        );
    }

    public function findByToken(Uuid $token): ?CourseDocumentDownload
    {
        return $this->findOneBy(['token' => $token]);
    }

    /**
     * Whether or not they were ever collected: a watermark names the moment it was made, so a stale artifact is not
     * worth keeping around to hand out later.
     *
     * @return CourseDocumentDownload[]
     */
    public function findExpired(DateTime $before): array
    {
        $qb = $this->createQueryBuilder('d');
        $qb->where('d.requestedAt < :before')
            ->setParameter(
                'before',
                $before,
            );

        return $qb->getQuery()->getResult();
    }
}
