<?php

declare(strict_types=1);

namespace App\Service\Career;

use App\Entity\Career\Company;
use App\Entity\Career\CompanyAuditLog;
use App\Entity\Career\Enums\CompanyAuditVerbs;
use App\Entity\User\CompanyUser;
use App\Entity\User\User;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Records one entry of a company's timeline. Only persists; the calling service flushes together with the change
 * itself, so an entry never outlives the thing it describes.
 */
final readonly class CompanyAuditLogger
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function log(
        Company $company,
        User|CompanyUser|null $actor,
        CompanyAuditVerbs $verb,
        string $detail = '',
    ): void {
        $entry = new CompanyAuditLog();
        $entry->setCompany($company);
        $entry->setVerb($verb);
        $entry->setDetail($detail);

        if ($actor instanceof User) {
            $entry->setActor($actor);
        } elseif ($actor instanceof CompanyUser) {
            $entry->setActorCompanyUser($actor);
        }

        $this->entityManager->persist($entry);
    }
}
