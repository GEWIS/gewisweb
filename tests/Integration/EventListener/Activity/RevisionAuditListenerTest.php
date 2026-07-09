<?php

declare(strict_types=1);

namespace App\Tests\Integration\EventListener\Activity;

use App\Entity\Activity\ActivityLabel;
use App\Entity\Activity\ActivityLocalisedText;
use App\Entity\Activity\ActivityRevision;
use App\Entity\Activity\ActivityRevisionEdit;
use App\Entity\Activity\SignupList;
use App\Entity\Application\Enums\RevisionStatus;
use App\Entity\User\User;
use App\Tests\Integration\DatabaseTestCase;
use DateTime;
use Symfony\Component\Uid\Uuid;

/**
 * The audit trail is emergent from a real Doctrine flush (it reads the UnitOfWork change sets in an onFlush listener),
 * so it can only be pinned with a real database. These tests prove that a member-driven edit of a Draft appends exactly
 * one attributable {@see ActivityRevisionEdit} naming the changed content fields, and that the three skip conditions
 * hold: a system flush (no editor), a flush once the revision has left Draft, and a change of only bookkeeping columns.
 */
final class RevisionAuditListenerTest extends DatabaseTestCase
{
    public function testAppendsAnAttributedAuditRowForAMemberEditOfADraft(): void
    {
        $revision = $this->aDraftRevision();
        $editor = $this->anEditor();
        $before = $this->auditCount($revision);

        $revision->setRequireGEFLITST(!$revision->getRequireGEFLITST());
        $revision->getName()->updateValueEN('Edited name');
        $revision->setLastEditedBy($editor);
        $this->entityManager->flush();

        self::assertSame(
            $before + 1,
            $this->auditCount($revision),
        );

        $latest = $this->latestEdit($revision);
        self::assertInstanceOf(
            ActivityRevisionEdit::class,
            $latest,
        );
        self::assertSame(
            $editor->getLidnr(),
            $latest->getEditor()->getLidnr(),
        );
        // Both the changed scalar and the edited localised text are recorded; bookkeeping columns are not.
        self::assertContains(
            'requireGEFLITST',
            $latest->getChangedFields(),
        );
        self::assertContains(
            'name',
            $latest->getChangedFields(),
        );
        self::assertNotContains(
            'lastEditedBy',
            $latest->getChangedFields(),
        );
    }

    public function testAuditsASignupListChangeOnADraftAsTheSignupListsMarker(): void
    {
        $revision = $this->aDraftRevision();
        $editor = $this->anEditor();
        $list = $this->persistedListOn($revision);
        $before = $this->auditCount($revision);

        // A capacity-only tweak to a draft's list is a content edit of the revision, surfaced via the synthetic marker.
        $list->setLimitedCapacity(true);
        $list->setCapacity(42);
        $revision->setLastEditedBy($editor);
        $this->entityManager->flush();

        self::assertSame(
            $before + 1,
            $this->auditCount($revision),
        );
        $latest = $this->latestEdit($revision);
        self::assertInstanceOf(
            ActivityRevisionEdit::class,
            $latest,
        );
        self::assertSame(
            ['signupLists'],
            $latest->getChangedFields(),
        );
    }

    public function testAuditsALabelChangeOnADraftAsTheLabelsMarker(): void
    {
        $revision = $this->aDraftRevision();
        $editor = $this->anEditor();
        $label = $this->entityManager->getRepository(ActivityLabel::class)->findOneBy([]);
        self::assertInstanceOf(
            ActivityLabel::class,
            $label,
            'The seed is expected to contain at least one activity label.',
        );
        $before = $this->auditCount($revision);

        // Adding a label to a draft is a reviewable content change, surfaced via the synthetic `labels` marker.
        $revision->addLabels([$label]);
        $revision->setLastEditedBy($editor);
        $this->entityManager->flush();

        self::assertSame(
            $before + 1,
            $this->auditCount($revision),
        );
        $latest = $this->latestEdit($revision);
        self::assertInstanceOf(
            ActivityRevisionEdit::class,
            $latest,
        );
        self::assertSame(
            ['labels'],
            $latest->getChangedFields(),
        );
    }

    public function testDoesNotAuditASystemFlushWithoutAnEditor(): void
    {
        $revision = $this->aDraftRevision();
        $before = $this->auditCount($revision);

        // No lastEditedBy set: a fixture/cron/approval-style flush is never attributed to a member.
        $revision->setRequireGEFLITST(!$revision->getRequireGEFLITST());
        $this->entityManager->flush();

        self::assertSame(
            $before,
            $this->auditCount($revision),
        );
    }

    public function testDoesNotAuditOnceTheRevisionHasLeftDraft(): void
    {
        $revision = $this->aDraftRevision();
        $editor = $this->anEditor();
        $before = $this->auditCount($revision);

        // In-place edits only happen on a Draft; a later flush carrying a stale editor must not append a phantom row.
        $revision->setStatus(RevisionStatus::Submitted);
        $revision->setRequireGEFLITST(!$revision->getRequireGEFLITST());
        $revision->setLastEditedBy($editor);
        $this->entityManager->flush();

        self::assertSame(
            $before,
            $this->auditCount($revision),
        );
    }

    public function testDoesNotAuditAChangeOfOnlyBookkeepingColumns(): void
    {
        $revision = $this->aDraftRevision();
        $editor = $this->anEditor();
        $before = $this->auditCount($revision);

        // Stamping the editor without touching any content leaves nothing audit-worthy, so no row is appended.
        $revision->setLastEditedBy($editor);
        $this->entityManager->flush();

        self::assertSame(
            $before,
            $this->auditCount($revision),
        );
    }

    private function aDraftRevision(): ActivityRevision
    {
        $revision = $this->entityManager->getRepository(ActivityRevision::class)
            ->findOneBy(['status' => RevisionStatus::Draft]);
        self::assertInstanceOf(
            ActivityRevision::class,
            $revision,
            'The seed is expected to contain a Draft activity revision.',
        );

        return $revision;
    }

    private function persistedListOn(ActivityRevision $revision): SignupList
    {
        $list = new SignupList();
        $list->setLineageId(Uuid::v4());
        $list->setName(new ActivityLocalisedText(
            'Lijst',
            'List',
        ));
        $list->setOpenDate(new DateTime('2026-01-01 00:00:00'));
        $list->setCloseDate(new DateTime('2026-12-31 00:00:00'));
        $revision->addSignupList($list);
        $this->entityManager->persist($list);
        // System flush (no editor set): creates the list without appending an audit row, so the subsequent change under
        // test is the only thing audited.
        $this->entityManager->flush();

        return $list;
    }

    private function anEditor(): User
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy([]);
        self::assertInstanceOf(
            User::class,
            $user,
            'The seed is expected to contain at least one user.',
        );

        return $user;
    }

    private function auditCount(ActivityRevision $revision): int
    {
        // ActivityRevisionEdit has no custom repository, so query it via DQL rather than the default repository.
        return (int) $this->entityManager->createQueryBuilder()
            ->select('COUNT(e.id)')
            ->from(
                ActivityRevisionEdit::class,
                'e',
            )
            ->where('e.revision = :revision')
            ->setParameter(
                'revision',
                $revision->getId(),
            )
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function latestEdit(ActivityRevision $revision): ?ActivityRevisionEdit
    {
        $edit = $this->entityManager->createQueryBuilder()
            ->select('e')
            ->from(
                ActivityRevisionEdit::class,
                'e',
            )
            ->where('e.revision = :revision')
            ->orderBy(
                'e.id',
                'DESC',
            )
            ->setParameter(
                'revision',
                $revision->getId(),
            )
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $edit instanceof ActivityRevisionEdit
            ? $edit
            : null;
    }
}
