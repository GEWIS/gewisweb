<?php

declare(strict_types=1);

namespace App\EventListener\Career;

use App\Entity\Application\Enums\Languages;
use App\Entity\Career\CompanyRevision;
use App\Entity\Career\VacancyRevision;
use App\Message\Career\CareerReviewDecisionEmail;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Workflow\Event\EnteredEvent;

/**
 * Writes to a company's representatives when the committee has decided. They have no notification centre of their own,
 * and a decision is exactly the kind of thing somebody needs to hear about without going looking, so it goes out as
 * plain email to everybody who can act for the company.
 */
#[AsEventListener(event: 'workflow.revision.entered.approved')]
#[AsEventListener(event: 'workflow.revision.entered.changes-requested')]
#[AsEventListener(event: 'workflow.revision.entered.rejected')]
final readonly class NotifyOnCareerDecisionListener
{
    public function __construct(private MessageBusInterface $messageBus)
    {
    }

    /**
     * @param EnteredEvent<object> $event
     */
    public function __invoke(EnteredEvent $event): void
    {
        $revision = $event->getSubject();

        if ($revision instanceof CompanyRevision) {
            $company = $revision->getCompany();
            $subjectName = $company->getName();
            $isVacancy = false;
        } elseif ($revision instanceof VacancyRevision) {
            $vacancy = $revision->getVacancy();
            $company = $vacancy->getCompany();
            $subjectName = $revision->getName()->getText(Languages::English) ?? '';
            $isVacancy = true;
        } else {
            return;
        }

        $companyId = $company->getId();
        if (null === $companyId) {
            return;
        }

        $this->messageBus->dispatch(new CareerReviewDecisionEmail(
            $companyId,
            $revision->getStatus(),
            $subjectName,
            $isVacancy,
        ));
    }
}
