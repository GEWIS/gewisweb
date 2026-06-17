<?php

declare(strict_types=1);

namespace App\Tests\Integration\Workflow;

use App\Entity\Activity\ActivityRevision;
use App\Entity\Application\Enums\RevisionStatus;
use App\Entity\User\User;
use App\Tests\Integration\DatabaseTestCase;
use DateTime;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Workflow\Registry;

/**
 * Proves the `revision` workflow is actually wired: that applying the `approve` transition flows through the guard
 * (board authorisation) and fires the entered/transition listeners the unit tests cover in isolation. A wrong event
 * name in workflow.yaml or a listener attribute would pass every unit test but fail here -- the live revision would not
 * be promoted and the review would not be stamped. Runs end to end against the real workflow + database.
 */
final class RevisionApprovalWiringTest extends DatabaseTestCase
{
    public function testApprovingAnActivityRevisionPromotesItLiveAndStampsTheReview(): void
    {
        $revision = $this->aBrandNewInReviewRevision();
        $activity = $revision->getActivity();

        // Neutralise the past-activity guard; the seed's dates are orthogonal here and already covered by
        // PastActivityGuardListenerTest.
        $revision->setBeginTime(new DateTime('+1 month'));
        $revision->setEndTime(new DateTime('+1 month +2 hours'));

        $this->authenticateBoardMember();

        $workflow = self::getContainer()->get(Registry::class)->get(
            $revision,
            'revision',
        );
        $workflow->apply(
            $revision,
            'approve',
        );
        $this->entityManager->flush();

        self::assertSame(
            RevisionStatus::Approved,
            $revision->getStatus(),
        );
        // MigrateSignupsOnApprovalListener (entered.approved) promoted the just-approved revision to live...
        self::assertSame(
            $revision,
            $activity->getLiveRevision(),
        );
        // ...and RevisionReviewStampListener (transition.approve) recorded who reviewed it and when.
        self::assertNotNull($revision->getReviewer());
        self::assertNotNull($revision->getReviewedAt());
    }

    private function aBrandNewInReviewRevision(): ActivityRevision
    {
        $revision = $this->entityManager->createQueryBuilder()
            ->select('r')
            ->from(
                ActivityRevision::class,
                'r',
            )
            ->join(
                'r.activity',
                'a',
            )
            ->where('r.status = :status')
            ->andWhere('a.liveRevision IS NULL')
            ->setParameter(
                'status',
                RevisionStatus::InReview,
            )
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
        self::assertInstanceOf(
            ActivityRevision::class,
            $revision,
            'The seed is expected to contain a brand-new (not-yet-live) in-review activity revision.',
        );

        return $revision;
    }

    private function authenticateBoardMember(): void
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy([]);
        self::assertInstanceOf(
            User::class,
            $user,
        );

        // The board role is all the approve guard checks; the user must be a real (seeded) account because the voter
        // and the review-stamp listener read its member.
        self::getContainer()->get('security.token_storage')->setToken(new UsernamePasswordToken(
            $user,
            'main',
            ['ROLE_BOARD'],
        ));
    }
}
