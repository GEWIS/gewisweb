<?php

declare(strict_types=1);

namespace App\Tests\Integration\Workflow;

use App\Entity\Application\Enums\NotificationType;
use App\Entity\Application\Enums\RevisionStatus;
use App\Entity\Frontpage\FrontpageLocalisedText;
use App\Entity\Frontpage\Poll;
use App\Entity\Frontpage\PollOption;
use App\Entity\Frontpage\PollRevision;
use App\Entity\User\User;
use App\Message\Application\PublishDomainNotificationMessage;
use App\Repository\Frontpage\PollRepository;
use App\Repository\Frontpage\PollRevisionRepository;
use App\Service\Frontpage\PollService;
use App\Tests\Integration\DatabaseTestCase;
use DateTime;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Workflow\Registry;
use Symfony\Component\Workflow\WorkflowInterface;

use function array_map;

/**
 * The generic revision workflow covers a poll by instanceof, and a poll is the one domain that deliberately does not
 * use all of it. These pin both halves: a question goes all the way round and becomes what the front page shows, and
 * the two ways a poll could grow something to edit stay shut.
 */
final class PollApprovalWiringTest extends DatabaseTestCase
{
    public function testAQuestionGoesFromRequestToLive(): void
    {
        $this->authenticate(8005);
        $poll = $this->request();
        $revision = $poll->getCurrentRevision();
        self::assertInstanceOf(
            PollRevision::class,
            $revision,
        );

        $this->authenticateBoard();

        foreach (
            [
                'start_review',
                'approve',
            ] as $transition
        ) {
            self::assertTrue(
                $this->workflow($revision)->can(
                    $revision,
                    $transition,
                ),
                $transition,
            );
            $this->workflow($revision)->apply(
                $revision,
                $transition,
            );
        }

        // Approving is also scheduling, which is why the date is filled in here rather than by whoever asked.
        $poll->setExpiryDate(new DateTime('+2 weeks'));
        $this->entityManager->flush();

        self::assertSame(
            RevisionStatus::Approved,
            $revision->getStatus(),
        );
        self::assertSame(
            $revision,
            $poll->getLiveRevision(),
        );
        self::assertTrue($poll->isActive());
    }

    public function testAQuestionWaitingForTheBoardTurnsUpInTheQueue(): void
    {
        $this->authenticate(8005);
        $revision = $this->request()->getCurrentRevision();

        self::assertContains(
            $revision,
            self::getContainer()->get(PollRevisionRepository::class)->findForReview(),
        );
    }

    /**
     * The notification is raised by a listener that silently skips a revision without an id, so this also pins that
     * the service persists before it submits.
     */
    public function testRequestingAPollTellsTheBoard(): void
    {
        $this->authenticate(8005);
        $this->request();

        self::assertContains(
            NotificationType::PollRevisionAwaitingReview,
            array_map(
                static fn (PublishDomainNotificationMessage $message): NotificationType => $message->getType(),
                $this->publishedNotifications(),
            ),
        );
    }

    /**
     * There is no draft to hand back, so the board is only ever offered yes or no.
     */
    public function testTheBoardIsNotOfferedToAskForChanges(): void
    {
        $this->authenticate(8005);
        $revision = $this->request()->getCurrentRevision();
        self::assertInstanceOf(
            PollRevision::class,
            $revision,
        );

        $this->authenticateBoard();
        $this->workflow($revision)->apply(
            $revision,
            'start_review',
        );

        self::assertFalse($this->workflow($revision)->can(
            $revision,
            'request_changes',
        ));
        self::assertTrue($this->workflow($revision)->can(
            $revision,
            'approve',
        ));
        self::assertTrue($this->workflow($revision)->can(
            $revision,
            'reject',
        ));
    }

    /**
     * A question members have started answering cannot be replaced underneath them, so a poll that is already live
     * takes no further submissions.
     */
    public function testAPublishedPollTakesNoSecondQuestion(): void
    {
        $poll = self::getContainer()->get(PollRepository::class)->findCurrentPoll();
        self::assertInstanceOf(
            Poll::class,
            $poll,
            'The seed is expected to contain a running poll.',
        );

        $this->authenticate($poll->getCreator()->getLidnr());

        $second = new PollRevision();
        $second->setQuestion(new FrontpageLocalisedText('And another thing?'));
        $poll->addRevision($second);

        self::assertFalse($this->workflow($second)->can(
            $second,
            'submit',
        ));
    }

    /**
     * A fresh question, asked the way the request form asks it.
     */
    private function request(): Poll
    {
        $revision = new PollRevision();
        $revision->setQuestion(new FrontpageLocalisedText('Should the coffee be free?'));

        foreach (
            [
                'Yes',
                'No',
            ] as $answer
        ) {
            $option = new PollOption();
            $option->setText(new FrontpageLocalisedText($answer));
            $revision->addOption($option);
        }

        return self::getContainer()->get(PollService::class)->requestPoll(
            $revision,
            $this->user(8005)->getMember(),
        );
    }

    private function authenticate(int $lidnr): void
    {
        self::getContainer()->get('security.token_storage')->setToken(new UsernamePasswordToken(
            $this->user($lidnr),
            'main',
            ['ROLE_USER'],
        ));
    }

    private function authenticateBoard(): void
    {
        self::getContainer()->get('security.token_storage')->setToken(new UsernamePasswordToken(
            $this->user(8000),
            'main',
            ['ROLE_BOARD'],
        ));
    }

    private function user(int $lidnr): User
    {
        $user = $this->entityManager->find(
            User::class,
            $lidnr,
        );
        self::assertInstanceOf(
            User::class,
            $user,
        );

        return $user;
    }

    /**
     * @return list<PublishDomainNotificationMessage>
     */
    private function publishedNotifications(): array
    {
        $messages = [];

        foreach (
            [
                'messenger.transport.normal_priority',
                'messenger.transport.high_priority',
            ] as $name
        ) {
            $transport = self::getContainer()->get($name);
            self::assertInstanceOf(
                InMemoryTransport::class,
                $transport,
            );

            foreach ($transport->getSent() as $envelope) {
                $message = $envelope->getMessage();
                if (!$message instanceof PublishDomainNotificationMessage) {
                    continue;
                }

                $messages[] = $message;
            }
        }

        return $messages;
    }

    private function workflow(PollRevision $revision): WorkflowInterface
    {
        return self::getContainer()->get(Registry::class)->get(
            $revision,
            'revision',
        );
    }
}
