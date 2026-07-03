<?php

declare(strict_types=1);

namespace App\Tests\Integration\Security\Application;

use App\Entity\Activity\ActivityRevision;
use App\Entity\Application\Enums\RevisionStatus;
use App\Entity\User\User;
use App\Security\Application\RevisionVoter;
use App\Tests\Integration\DatabaseTestCase;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

use function sprintf;

/**
 * {@see RevisionVoter} grants edit/view rights to the members of the organ organising a revision, resolved from the
 * member's live organ installations against the WORKING revision's organ. The unit test pins the decision matrix with
 * stubs; this proves the real wiring against the seed: a member of the organising organ may see and (while it is a
 * Draft) edit the revision, a member of a different organ may not, and only a Draft is editable.
 *
 * The authenticated members carry plain `ROLE_USER` (no board), so the reviewer short-circuit never fires and the
 * decision rests purely on ownership.
 */
final class RevisionVoterOrganMembershipTest extends DatabaseTestCase
{
    public function testAnOrganMemberMayViewTheirOrgansRevision(): void
    {
        // 8005 is installed in GETÉST but neither created nor authored the Hackathon; only organ membership reaches it.
        $revision = $this->revisionForOrgan(
            'GETÉST',
            RevisionStatus::InReview,
        );
        $this->authenticate(8005);

        self::assertTrue(self::getContainer()->get('security.authorization_checker')->isGranted(
            RevisionVoter::VIEW,
            $revision,
        ));
    }

    public function testAMemberOfAnotherOrganMayNotViewTheRevision(): void
    {
        // 8027 is installed in KEUR, not GETÉST, and is neither creator nor author, so the organ scope does not reach
        // them: organ access is specific, not "any organ I am in".
        $revision = $this->revisionForOrgan(
            'GETÉST',
            RevisionStatus::InReview,
        );
        $this->authenticate(8027);

        self::assertFalse(self::getContainer()->get('security.authorization_checker')->isGranted(
            RevisionVoter::VIEW,
            $revision,
        ));
    }

    public function testAnOrganMemberMayEditADraftButNotARevisionThatHasLeftDraft(): void
    {
        $this->authenticate(8005);

        // A Draft is editable in place by its organ ...
        self::assertTrue(self::getContainer()->get('security.authorization_checker')->isGranted(
            RevisionVoter::EDIT,
            $this->revisionForOrgan(
                'GETÉST',
                RevisionStatus::Draft,
            ),
        ));
        // ... but an in-review revision is immutable (it must be revised through a freshly spawned draft instead), even
        // for the organ.
        self::assertFalse(self::getContainer()->get('security.authorization_checker')->isGranted(
            RevisionVoter::EDIT,
            $this->revisionForOrgan(
                'GETÉST',
                RevisionStatus::InReview,
            ),
        ));
    }

    private function authenticate(int $lidnr): void
    {
        $user = $this->entityManager->getRepository(User::class)->find($lidnr);
        self::assertInstanceOf(
            User::class,
            $user,
            sprintf(
                'The seed is expected to contain a user for member %d.',
                $lidnr,
            ),
        );

        self::getContainer()->get('security.token_storage')->setToken(new UsernamePasswordToken(
            $user,
            'main',
            ['ROLE_USER'],
        ));
    }

    /**
     * The working revision of the activity organised by the given organ and in the given state.
     */
    private function revisionForOrgan(
        string $abbr,
        RevisionStatus $status,
    ): ActivityRevision {
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
            ->join(
                'r.organ',
                'o',
            )
            ->where('o.abbr = :abbr')
            ->andWhere('r.status = :status')
            ->andWhere('a.currentRevision = r')
            ->setParameter(
                'abbr',
                $abbr,
            )
            ->setParameter(
                'status',
                $status->value,
            )
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
        self::assertInstanceOf(
            ActivityRevision::class,
            $revision,
            sprintf(
                'The seed is expected to contain a %s revision organised by %s.',
                $status->value,
                $abbr,
            ),
        );

        return $revision;
    }
}
