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
 * On approval a revision becomes the publicly live version of its aggregate. This is the single promoter for every
 * domain (companies, vacancies and activities alike); activities additionally have their sign-ups migrated first by
 * {@see \App\EventListener\Activity\MigrateSignupsOnApprovalListener}, which runs at a higher priority so it reads the
 * still-current live revision before this listener repoints it.
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

    public function testPromotesAnApprovedActivityRevision(): void
    {
        $activity = new Activity();
        $revision = new ActivityRevision();
        $activity->addRevision($revision);

        $this->listener->__invoke($this->enteredEvent($revision));

        self::assertSame(
            $revision,
            $activity->getLiveRevision(),
            'this listener promotes every domain, activities included (sign-up migration runs first, separately)',
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
