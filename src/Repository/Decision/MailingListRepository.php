<?php

declare(strict_types=1);

namespace App\Repository\Decision;

use App\Entity\Decision\MailingList;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MailingList>
 */
class MailingListRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            MailingList::class,
        );
    }
}
