<?php

declare(strict_types=1);

namespace App\Tests\Integration\Workflow;

use App\Entity\Activity\ActivityRevision;
use App\Entity\Application\Enums\RevisionStatus;
use App\Entity\Application\RevisionInterface;
use App\Entity\User\User;
use App\Repository\Activity\ActivityRevisionRepository;
use App\Tests\Integration\DatabaseTestCase;
use DateTime;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Workflow\Registry;
use Symfony\Component\Workflow\WorkflowInterface;

/**
 * How long something has been waiting on the board is what a review queue is read by, and a draft can be written long
 * before it is handed over. These pin that the moment recorded is the handing over rather than the writing.
 */
final class RevisionSubmissionStampTest extends DatabaseTestCase
{
    public function testADraftCarriesNoSubmissionMoment(): void
    {
        self::assertNull($this->draft()->getSubmittedAt());
    }

    public function testSubmittingDatesTheRevision(): void
    {
        $draft = $this->draft();
        $this->authenticate($draft);

        $this->workflow($draft)->apply(
            $draft,
            'submit',
        );
        $this->entityManager->flush();

        self::assertSame(
            RevisionStatus::Submitted,
            $draft->getStatus(),
        );
        self::assertNotNull($draft->getSubmittedAt());
    }

    /**
     * Any draft the seed left behind: what is in it does not matter here, only that nobody has submitted it.
     */
    private function draft(): ActivityRevision
    {
        foreach (self::getContainer()->get(ActivityRevisionRepository::class)->findAll() as $revision) {
            if (
                RevisionStatus::Draft !== $revision->getStatus()
                || $revision->getActivity()->getBeginTime() < new DateTime()
            ) {
                continue;
            }

            return $revision;
        }

        self::fail('The seed is expected to contain a draft activity that has not happened yet.');
    }

    /**
     * As whoever wrote it: submitting is the author's own move, and the workflow guards it as such.
     */
    private function authenticate(ActivityRevision $draft): void
    {
        $author = $draft->getAuthor();
        self::assertNotNull($author);

        $user = $this->entityManager->find(
            User::class,
            $author->getLidnr(),
        );
        self::assertInstanceOf(
            User::class,
            $user,
        );

        self::getContainer()->get('security.token_storage')->setToken(new UsernamePasswordToken(
            $user,
            'main',
            ['ROLE_USER'],
        ));
    }

    private function workflow(RevisionInterface $revision): WorkflowInterface
    {
        return self::getContainer()->get(Registry::class)->get(
            $revision,
            'revision',
        );
    }
}
