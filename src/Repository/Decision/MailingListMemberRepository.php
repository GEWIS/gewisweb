<?php

declare(strict_types=1);

namespace App\Repository\Decision;

use App\Entity\Decision\MailingListMember;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MailingListMember>
 */
class MailingListMemberRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            MailingListMember::class,
        );
    }
}
