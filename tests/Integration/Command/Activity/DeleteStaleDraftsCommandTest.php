<?php

declare(strict_types=1);

namespace App\Tests\Integration\Command\Activity;

use App\Command\Activity\DeleteStaleDraftsCommand;
use App\Entity\Activity\Activity;
use App\Entity\Activity\ActivityRevision;
use App\Entity\Application\Enums\RevisionStatus;
use App\Service\Activity\ActivityRevisionCloner;
use App\Tests\Integration\DatabaseTestCase;
use DateTime;
use Doctrine\DBAL\Types\Types;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * The stale-draft cleanup is a GDPR cron, so its branches are pinned end to end against a real database: an abandoned
 * re-edit of an approved activity is reverted to its live revision, an abandoned never-approved draft activity is
 * removed entirely, and a dry run reports without touching anything. Staleness is forced by ageing the draft's
 * (auto-stamped) `updatedAt` past the 30-day cutoff with a DQL update.
 *
 * Not covered here: the defensive skip when a never-approved activity already carries sign-ups. That state never
 * arises from the normal flow (sign-ups only exist on an approved revision) and is left to the unit-level guards.
 */
final class DeleteStaleDraftsCommandTest extends DatabaseTestCase
{
    public function testRevertsAStaleReEditToItsApprovedRevision(): void
    {
        $activity = $this->anApprovedActivityWithoutSignupLists();
        $live = $activity->getLiveRevision();
        self::assertInstanceOf(
            ActivityRevision::class,
            $live,
        );

        // An abandoned re-edit: a Draft head spawned from the live revision, untouched for longer than the cutoff.
        $draft = $this->cloner()->cloneAsDraft($live);
        self::assertInstanceOf(
            ActivityRevision::class,
            $draft,
        );
        $this->entityManager->persist($draft);
        $this->entityManager->flush();
        $draftId = (int) $draft->getId();
        $this->ageRevision(
            $draftId,
            '-40 days',
        );

        $this->runCommand();

        // The activity is back on its approved revision and the abandoned draft is gone.
        self::assertSame(
            $live,
            $activity->getCurrentRevision(),
        );
        self::assertNull(
            $this->entityManager->getRepository(ActivityRevision::class)->find($draftId),
        );
    }

    public function testDeletesAStaleNeverApprovedDraftActivityEntirely(): void
    {
        $draft = $this->aNeverApprovedDraft();
        $activityId = (int) $draft->getActivity()->getId();
        $this->ageRevision(
            (int) $draft->getId(),
            '-40 days',
        );

        $this->runCommand();

        // With no live revision to fall back to, the whole activity (every revision in its chain) is removed.
        self::assertNull(
            $this->entityManager->getRepository(Activity::class)->find($activityId),
        );
    }

    public function testDryRunReportsButChangesNothing(): void
    {
        $draft = $this->aNeverApprovedDraft();
        $activityId = (int) $draft->getActivity()->getId();
        $draftId = (int) $draft->getId();
        $this->ageRevision(
            $draftId,
            '-40 days',
        );

        $this->runCommand(['--dry-run' => true]);

        // The stale draft and its activity are reported but left in place.
        self::assertNotNull(
            $this->entityManager->getRepository(Activity::class)->find($activityId),
        );
        self::assertNotNull(
            $this->entityManager->getRepository(ActivityRevision::class)->find($draftId),
        );
    }

    /**
     * @param array<string, bool|string> $input
     */
    private function runCommand(array $input = []): void
    {
        $tester = new CommandTester(self::getContainer()->get(DeleteStaleDraftsCommand::class));
        $tester->execute($input);
        $tester->assertCommandIsSuccessful();
    }

    private function cloner(): ActivityRevisionCloner
    {
        return self::getContainer()->get(ActivityRevisionCloner::class);
    }

    /**
     * Age a revision past the staleness cutoff. `updatedAt` is auto-stamped by a lifecycle callback, so a DQL update
     * (which bypasses the unit of work) is the only way to backdate it; the repository's stale-draft query reads the
     * database, so the aged value is seen without refreshing the managed entity.
     */
    private function ageRevision(
        int $revisionId,
        string $modifier,
    ): void {
        $this->entityManager->createQueryBuilder()
            ->update(
                ActivityRevision::class,
                'r',
            )
            ->set(
                'r.updatedAt',
                ':past',
            )
            ->where('r.id = :id')
            ->setParameter(
                'past',
                new DateTime($modifier),
                Types::DATETIME_MUTABLE,
            )
            ->setParameter(
                'id',
                $revisionId,
            )
            ->getQuery()
            ->execute();
    }

    private function anApprovedActivityWithoutSignupLists(): Activity
    {
        $activity = $this->entityManager->createQueryBuilder()
            ->select('a')
            ->from(
                Activity::class,
                'a',
            )
            ->join(
                'a.liveRevision',
                'lr',
            )
            ->where('a.currentRevision = a.liveRevision')
            ->andWhere('SIZE(lr.signupLists) = 0')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
        self::assertInstanceOf(
            Activity::class,
            $activity,
            'The seed is expected to contain an approved activity without sign-up lists.',
        );

        return $activity;
    }

    private function aNeverApprovedDraft(): ActivityRevision
    {
        $draft = $this->entityManager->createQueryBuilder()
            ->select('r')
            ->from(
                ActivityRevision::class,
                'r',
            )
            ->join(
                'r.activity',
                'a',
            )
            ->where('r.status = :draft')
            ->andWhere('a.liveRevision IS NULL')
            ->andWhere('a.currentRevision = r')
            ->setParameter(
                'draft',
                RevisionStatus::Draft->value,
            )
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
        self::assertInstanceOf(
            ActivityRevision::class,
            $draft,
            'The seed is expected to contain a never-approved draft activity (the changes-requested example).',
        );

        return $draft;
    }
}
