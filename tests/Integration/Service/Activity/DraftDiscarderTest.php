<?php

declare(strict_types=1);

namespace App\Tests\Integration\Service\Activity;

use App\Entity\Activity\Activity;
use App\Entity\Activity\ActivityRevision;
use App\Entity\Activity\ActivityRevisionComment;
use App\Entity\User\User;
use App\Service\Activity\ActivityRevisionCloner;
use App\Service\Activity\DraftDiscarder;
use App\Tests\Integration\DatabaseTestCase;

/**
 * Discarding a draft re-edit must put the aggregate back exactly as it was: the activity points at its approved (live)
 * revision again and the abandoned draft is gone together with its review thread. The comments matter because they
 * reference the revision with a NON-cascading foreign key, so a plain `remove($revision)` would fail on them; the
 * service removes them first. Set up against a real re-edit produced by {@see ActivityRevisionCloner}, the same way the
 * application spawns the draft.
 */
final class DraftDiscarderTest extends DatabaseTestCase
{
    public function testDiscardToLiveRevertsToTheApprovedRevisionAndRemovesTheDraftAndItsComments(): void
    {
        $activity = $this->anApprovedActivityWithoutSignupLists();
        $live = $activity->getLiveRevision();
        self::assertInstanceOf(
            ActivityRevision::class,
            $live,
        );

        // Reproduce the "re-edit an approved activity" state: a fresh Draft head spawned from the live revision, with a
        // review comment on it.
        $draft = $this->cloner()->cloneAsDraft($live);
        self::assertInstanceOf(
            ActivityRevision::class,
            $draft,
        );
        $this->entityManager->persist($draft);
        $comment = $this->commentOn($draft);
        $this->entityManager->flush();

        $draftId = (int) $draft->getId();
        $commentId = (int) $comment->getId();
        // Sanity: the activity is now working on the draft, not the live revision.
        self::assertSame(
            $draft,
            $activity->getCurrentRevision(),
        );

        $this->discarder()->discardToLive($draft);
        $this->entityManager->flush();

        // The activity falls back to its approved revision ...
        self::assertSame(
            $live,
            $activity->getCurrentRevision(),
        );
        // ... and the draft together with its (non-cascading) review thread is gone.
        self::assertNull(
            $this->entityManager->getRepository(ActivityRevision::class)->find($draftId),
        );
        self::assertNull(
            $this->entityManager->getRepository(ActivityRevisionComment::class)->find($commentId),
        );
    }

    private function cloner(): ActivityRevisionCloner
    {
        return self::getContainer()->get(ActivityRevisionCloner::class);
    }

    private function discarder(): DraftDiscarder
    {
        return self::getContainer()->get(DraftDiscarder::class);
    }

    /**
     * An approved activity whose live revision carries no sign-up lists, so cloning it into a draft needs no extra
     * cascade handling and the discard path stays the subject under test.
     */
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

    private function commentOn(ActivityRevision $revision): ActivityRevisionComment
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy([]);
        self::assertInstanceOf(
            User::class,
            $user,
        );

        $comment = new ActivityRevisionComment();
        $comment->setRevision($revision);
        $comment->setAuthor($user);
        $comment->setBody('Please reconsider the location.');
        $this->entityManager->persist($comment);

        return $comment;
    }
}
