<?php

declare(strict_types=1);

namespace App\Tests\EventListener\Application;

use App\Entity\Activity\Activity;
use App\Entity\Activity\ActivityRevision;
use App\Entity\Career\Company;
use App\Entity\Career\CompanyRevision;
use App\Entity\Career\Vacancy;
use App\Entity\Career\VacancyRevision;
use App\EventListener\Application\PromoteLiveRevisionListener;
use Override;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Workflow\Event\EnteredEvent;
use Symfony\Component\Workflow\Marking;

/**
 * On approval a revision becomes the publicly live version of its aggregate, except for activities, which are
 * promoted by {@see \App\EventListener\Activity\MigrateSignupsOnApprovalListener} (it must first move the live sign-ups
 * across). This listener therefore promotes companies and vacancies directly and must skip activity revisions, or an
 * activity would be promoted without its sign-ups migrated.
 */
final class PromoteLiveRevisionListenerTest extends TestCase
{
    private PromoteLiveRevisionListener $listener;

    #[Override]
    protected function setUp(): void
    {
        $this->listener = new PromoteLiveRevisionListener();
    }

    public function testPromotesAnApprovedCompanyRevision(): void
    {
        $company = new Company();
        $revision = new CompanyRevision();
        $company->addRevision($revision);

        $this->listener->__invoke($this->enteredEvent($revision));

        self::assertSame(
            $revision,
            $company->getLiveRevision(),
        );
    }

    public function testPromotesAnApprovedVacancyRevision(): void
    {
        $vacancy = new Vacancy();
        $revision = new VacancyRevision();
        $vacancy->addRevision($revision);

        $this->listener->__invoke($this->enteredEvent($revision));

        self::assertSame(
            $revision,
            $vacancy->getLiveRevision(),
        );
    }

    public function testSkipsActivityRevisionsWhichArePromotedAfterSignupMigration(): void
    {
        $activity = new Activity();
        $revision = new ActivityRevision();
        $activity->addRevision($revision);

        $this->listener->__invoke($this->enteredEvent($revision));

        self::assertNull(
            $activity->getLiveRevision(),
            'an activity must not be promoted by this listener, only after its sign-ups are migrated',
        );
    }

    private function enteredEvent(object $subject): EnteredEvent
    {
        return new EnteredEvent(
            $subject,
            new Marking([]),
        );
    }
}
