<?php

declare(strict_types=1);

namespace App\Tests\Integration\Workflow;

use App\Entity\Application\Enums\NotificationType;
use App\Entity\Application\Enums\RevisionStatus;
use App\Entity\Application\Enums\SocialPlatform;
use App\Entity\Decision\DecisionLocalisedText;
use App\Entity\Decision\OrganInformation;
use App\Entity\Decision\OrganInformationRevision;
use App\Entity\User\User;
use App\Message\Application\PublishDomainNotificationMessage;
use App\Repository\Decision\OrganInformationRepository;
use App\Repository\Decision\OrganInformationRevisionRepository;
use App\Service\Application\RevisionDiscarder;
use App\Tests\Integration\DatabaseTestCase;
use App\Workflow\RevisionClonerRegistry;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Workflow\Registry;
use Symfony\Component\Workflow\WorkflowInterface;

use function array_map;

/**
 * The generic revision workflow covers a body's page by instanceof, so nothing about it was written for one. These pin
 * that it genuinely does: a page goes all the way round and becomes what the website shows, turns up in the board's
 * queue while it waits, and leaves the body a fresh draft when the board asks for changes.
 */
final class OrganApprovalWiringTest extends DatabaseTestCase
{
    public function testAPageGoesFromDraftToLive(): void
    {
        $this->authenticateBoard();
        $information = $this->page();
        $live = $information->getLiveRevision();
        $draft = $this->draft();

        foreach (
            [
                'submit',
                'start_review',
                'approve',
            ] as $transition
        ) {
            self::assertTrue(
                $this->workflow($draft)->can(
                    $draft,
                    $transition,
                ),
                $transition,
            );
            $this->workflow($draft)->apply(
                $draft,
                $transition,
            );
        }

        $this->entityManager->flush();

        self::assertSame(
            RevisionStatus::Approved,
            $draft->getStatus(),
        );
        // Approving is what puts it on the website: the page now points at the new revision instead of the old one.
        self::assertSame(
            $draft,
            $information->getLiveRevision(),
        );
        self::assertNotSame(
            $live,
            $information->getLiveRevision(),
        );
    }

    public function testAPageWaitingForTheBoardTurnsUpInTheQueue(): void
    {
        $this->authenticateBoard();
        $draft = $this->draft();
        $this->workflow($draft)->apply(
            $draft,
            'submit',
        );
        $this->entityManager->flush();

        self::assertContains(
            $draft,
            self::getContainer()->get(OrganInformationRevisionRepository::class)->findForReview(),
        );
    }

    public function testSubmittingAPageTellsTheBoard(): void
    {
        $this->authenticateBoard();
        $draft = $this->draft();
        $this->workflow($draft)->apply(
            $draft,
            'submit',
        );
        $this->entityManager->flush();

        self::assertContains(
            NotificationType::OrganInformationRevisionAwaitingReview,
            array_map(
                static fn (PublishDomainNotificationMessage $message): NotificationType => $message->getType(),
                $this->publishedNotifications(),
            ),
        );
    }

    public function testAskingForChangesLeavesTheBodyANewDraft(): void
    {
        $this->authenticateBoard();
        $information = $this->page();
        $draft = $this->draft();

        foreach (
            [
                'submit',
                'start_review',
                'request_changes',
            ] as $transition
        ) {
            $this->workflow($draft)->apply(
                $draft,
                $transition,
            );
        }

        $this->entityManager->flush();

        $next = $information->getCurrentRevision();
        self::assertInstanceOf(
            OrganInformationRevision::class,
            $next,
        );
        self::assertNotSame(
            $draft,
            $next,
        );
        self::assertSame(
            RevisionStatus::Draft,
            $next->getStatus(),
        );
        self::assertSame(
            $draft,
            $next->getPreviousRevision(),
        );
    }

    /**
     * A draft's own localised texts and social links are its alone, so throwing it away has to take them with it and
     * leave what is live untouched.
     */
    public function testDiscardingADraftPutsTheLivePageBack(): void
    {
        $this->authenticateBoard();
        $information = $this->page();
        $live = $information->getLiveRevision();
        self::assertInstanceOf(
            OrganInformationRevision::class,
            $live,
        );
        $liveHandles = $live->getSocialHandles();

        $draft = $this->draft();
        $draft->setShortDescription(new DecisionLocalisedText(
            'Changed',
            'Gewijzigd',
        ));
        $draft->updateSocialLinks([SocialPlatform::Twitch->value => 'somethingelse']);
        $this->entityManager->flush();
        $draftId = (int) $draft->getId();

        self::getContainer()->get(RevisionDiscarder::class)->discardToLive($draft);
        $this->entityManager->flush();

        self::assertSame(
            $live,
            $information->getCurrentRevision(),
        );
        self::assertNull($this->entityManager->getRepository(OrganInformationRevision::class)->find($draftId));
        self::assertSame(
            $liveHandles,
            $live->getSocialHandles(),
        );
    }

    /**
     * A body's page is reviewed by the board and by nobody else, so the board role is what the guards check. The
     * account must be a real seeded one, since the voter and the review-stamp listener read its member.
     */
    private function authenticateBoard(): void
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy([]);
        self::assertInstanceOf(
            User::class,
            $user,
        );

        self::getContainer()->get('security.token_storage')->setToken(new UsernamePasswordToken(
            $user,
            'main',
            ['ROLE_BOARD'],
        ));
    }

    private function page(string $abbr = 'GETÉST'): OrganInformation
    {
        $information = null;

        foreach (self::getContainer()->get(OrganInformationRepository::class)->findAll() as $candidate) {
            if ($candidate->getOrgan()->getAbbr() !== $abbr) {
                continue;
            }

            $information = $candidate;
        }

        self::assertInstanceOf(
            OrganInformation::class,
            $information,
            'The seed is expected to contain a page for this body.',
        );

        return $information;
    }

    private function draft(string $abbr = 'GETÉST'): OrganInformationRevision
    {
        $current = $this->page($abbr)->getCurrentRevision();
        self::assertInstanceOf(
            OrganInformationRevision::class,
            $current,
        );

        $draft = self::getContainer()->get(RevisionClonerRegistry::class)->cloneAsDraft($current);
        self::assertInstanceOf(
            OrganInformationRevision::class,
            $draft,
        );
        $this->entityManager->persist($draft);
        $this->entityManager->flush();

        return $draft;
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

    private function workflow(OrganInformationRevision $revision): WorkflowInterface
    {
        return self::getContainer()->get(Registry::class)->get(
            $revision,
            'revision',
        );
    }
}
