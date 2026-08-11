<?php

declare(strict_types=1);

namespace App\Tests\Entity\Application;

use App\Entity\Activity\Activity;
use App\Entity\Activity\ActivityRevision;
use App\Entity\Activity\ActivityRevisionComment;
use App\Entity\Application\AbstractRevisionComment;
use App\Entity\Application\RevisableInterface;
use App\Entity\Application\RevisionInterface;
use App\Entity\Career\Company;
use App\Entity\Career\CompanyRevision;
use App\Entity\Career\CompanyRevisionComment;
use App\Entity\Career\Vacancy;
use App\Entity\Career\VacancyRevision;
use App\Entity\Career\VacancyRevisionComment;
use App\Entity\Decision\OrganInformation;
use App\Entity\Decision\OrganInformationRevision;
use App\Entity\Decision\OrganInformationRevisionComment;
use App\Entity\User\Enums\UserRoles;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

use function array_unique;
use function is_subclass_of;

/**
 * The contract every revisable domain answers, exercised against all of them at once. Code that works across
 * domains, the discarder, the review controllers and the voter, leans on exactly these methods, so a domain that gets
 * one of them wrong should fail here rather than at the far end of a review screen.
 */
final class RevisionContractsTest extends TestCase
{
    /**
     * @return iterable<string, array{RevisableInterface, RevisionInterface, RevisionInterface}>
     */
    public static function domains(): iterable
    {
        $activity = new Activity();
        $activityLive = new ActivityRevision();
        $activityDraft = new ActivityRevision();
        $activity->addRevision($activityLive);
        $activity->addRevision($activityDraft);
        $activity->setLiveRevision($activityLive);
        $activity->setCurrentRevision($activityDraft);

        yield 'activity' => [
            $activity,
            $activityLive,
            $activityDraft,
        ];

        $company = new Company();
        $companyLive = new CompanyRevision();
        $companyDraft = new CompanyRevision();
        $company->addRevision($companyLive);
        $company->addRevision($companyDraft);
        $company->setLiveRevision($companyLive);
        $company->setCurrentRevision($companyDraft);

        yield 'company' => [
            $company,
            $companyLive,
            $companyDraft,
        ];

        $vacancy = new Vacancy();
        $vacancyLive = new VacancyRevision();
        $vacancyDraft = new VacancyRevision();
        $vacancy->addRevision($vacancyLive);
        $vacancy->addRevision($vacancyDraft);
        $vacancy->setLiveRevision($vacancyLive);
        $vacancy->setCurrentRevision($vacancyDraft);

        yield 'vacancy' => [
            $vacancy,
            $vacancyLive,
            $vacancyDraft,
        ];

        $information = new OrganInformation();
        $informationLive = new OrganInformationRevision();
        $informationDraft = new OrganInformationRevision();
        $information->addRevision($informationLive);
        $information->addRevision($informationDraft);
        $information->setLiveRevision($informationLive);
        $information->setCurrentRevision($informationDraft);

        yield 'organ information' => [
            $information,
            $informationLive,
            $informationDraft,
        ];
    }

    #[DataProvider('domains')]
    public function testRestoringTheLiveRevisionDropsTheDraftInFrontOfIt(
        RevisableInterface $revisable,
        RevisionInterface $live,
        RevisionInterface $draft,
    ): void {
        self::assertSame(
            $draft,
            $revisable->getCurrentRevision(),
        );

        $revisable->restoreLiveRevision();

        self::assertSame(
            $live,
            $revisable->getCurrentRevision(),
        );
    }

    #[DataProvider('domains')]
    public function testTheCommentClassIsAThreadEntryThatTakesItsOwnRevision(
        RevisableInterface $revisable,
        RevisionInterface $live,
        RevisionInterface $draft,
    ): void {
        $class = $draft->getCommentClass();
        self::assertTrue(is_subclass_of(
            $class,
            AbstractRevisionComment::class,
        ));

        $comment = new $class();
        $comment->attachTo($draft);

        self::assertSame(
            $draft,
            $comment->getRevision(),
        );
    }

    /**
     * A comment belongs to one chain; attaching it to another domain's revision is a programming error, not something
     * to discover once the thread renders.
     */
    public function testACommentRefusesARevisionFromAnotherDomain(): void
    {
        $this->expectException(RuntimeException::class);

        new CompanyRevisionComment()->attachTo(new ActivityRevision());
    }

    public function testEveryDomainDeclaresWhoReviewsItBesidesTheBoard(): void
    {
        self::assertSame(
            [],
            new Activity()->getReviewerRoles(),
        );
        self::assertSame(
            [UserRoles::CompanyAdmin],
            new Company()->getReviewerRoles(),
        );
        self::assertSame(
            [UserRoles::CompanyAdmin],
            new Vacancy()->getReviewerRoles(),
        );
        self::assertSame(
            [],
            new OrganInformation()->getReviewerRoles(),
        );
    }

    public function testTheCommentClassesAreDistinctPerDomain(): void
    {
        self::assertSame(
            ActivityRevisionComment::class,
            new ActivityRevision()->getCommentClass(),
        );
        self::assertSame(
            CompanyRevisionComment::class,
            new CompanyRevision()->getCommentClass(),
        );
        self::assertSame(
            VacancyRevisionComment::class,
            new VacancyRevision()->getCommentClass(),
        );
        self::assertSame(
            OrganInformationRevisionComment::class,
            new OrganInformationRevision()->getCommentClass(),
        );
    }

    /**
     * Every domain answers with its own resource id, since the edit lock keys on it: two domains sharing one would
     * let a body's page and an activity with the same number lock each other out.
     */
    public function testTheResourceIdsAreDistinctPerDomain(): void
    {
        $ids = [
            new Activity()->getResourceId(),
            new Company()->getResourceId(),
            new Vacancy()->getResourceId(),
            new OrganInformation()->getResourceId(),
        ];

        self::assertSame(
            $ids,
            array_unique($ids),
        );
    }
}
