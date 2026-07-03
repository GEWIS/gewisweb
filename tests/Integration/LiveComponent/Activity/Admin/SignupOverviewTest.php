<?php

declare(strict_types=1);

namespace App\Tests\Integration\LiveComponent\Activity\Admin;

use App\Entity\Activity\Activity;
use App\Entity\Activity\SignupList;
use App\Entity\Application\Enums\AlertTypes;
use App\Entity\User\User;
use App\Message\Activity\OrganiserAnnouncementEmail;
use App\Tests\Integration\DatabaseTestCase;
use App\Twig\Components\Activity\Admin\SignupOverview;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * The sign-up admin component is the first live component that writes to the database and dispatches mail, so it
 * re-asserts access on every action rather than trusting the page that embedded it. These exercise it as the framework
 * does -- the real component instance with its real services -- after authenticating the current user on the token
 * storage, which is what its {@see SignupOverview::assertAccess()} reads.
 *
 * Driving it over the live-component HTTP endpoint is not viable here: the app's session guard force-logs-out any
 * session not backed by a managed-session row, so a synthetic browser session never survives. The class-level
 * `#[IsGranted('SUDO')]` is therefore enforced by the framework at that HTTP layer, not exercised here; the substantive
 * per-action authorisation ({@see SignupOverview::assertAccess()}, board-only draws) is.
 *
 * Activity #9 (the Gala) has one limited list (#6) with four subscribers; activity #13 (the Excursion) has a closed,
 * not-yet-drawn limited list (#11, capacity 2, four sign-ups) ready for a draw.
 */
final class SignupOverviewTest extends DatabaseTestCase
{
    public function testSelectAllThenClearSelectionScopesToTheList(): void
    {
        $this->authenticate(['ROLE_BOARD']);
        $component = $this->overviewFor(9);

        $component->selectAll(6);
        self::assertCount(
            4,
            $component->selected,
        );

        $component->clearSelection(6);
        self::assertCount(
            0,
            $component->selected,
        );
    }

    public function testToggleFieldColumnFlipsHiddenState(): void
    {
        $this->authenticate(['ROLE_BOARD']);
        $component = $this->overviewFor(9);

        $component->toggleFieldColumn(2);
        self::assertContains(
            2,
            $component->hiddenFields,
        );

        $component->toggleFieldColumn(2);
        self::assertNotContains(
            2,
            $component->hiddenFields,
        );
    }

    public function testSendEmailDispatchesAnAnnouncementToTheListRecipients(): void
    {
        $this->authenticate(['ROLE_BOARD']);
        $component = $this->overviewFor(9);
        $component->emailSubject = 'See you at the Gala';
        $component->emailBody = 'Doors open at 17:00.';

        $component->sendEmail(6);

        $sent = $this->bulkMessages();
        self::assertCount(
            1,
            $sent,
        );
        // All four subscribers carry an e-mail, so the default "everyone" scope reaches all of them.
        self::assertCount(
            4,
            $sent[0]->getRecipients(),
        );
    }

    public function testSendEmailWithoutASubjectDispatchesNothing(): void
    {
        $this->authenticate(['ROLE_BOARD']);
        $component = $this->overviewFor(9);
        $component->emailBody = 'A body, but no subject.';

        $component->sendEmail(6);

        self::assertSame(
            [],
            $this->bulkMessages(),
        );
        self::assertSame(
            AlertTypes::Warning->value,
            $component->feedbackType,
        );
    }

    public function testDrawAdmitsUpToCapacityAndLocksTheList(): void
    {
        $this->authenticate(['ROLE_BOARD']);
        $component = $this->overviewFor(13);

        $component->drawLottery(11);

        $list = $this->entityManager->getRepository(SignupList::class)->find(11);
        self::assertInstanceOf(
            SignupList::class,
            $list,
        );
        // Capacity is two, so exactly two of the four sign-ups are admitted and the draw is locked with an audit stamp.
        $drawn = 0;
        foreach ($list->getSignUps() as $signup) {
            if (!$signup->isDrawn()) {
                continue;
            }

            ++$drawn;
        }

        self::assertSame(
            2,
            $drawn,
        );
        self::assertNotNull($list->getDrawnAt());
        self::assertNotNull($list->getDrawnBy());
    }

    public function testAnActionIsDeniedForANonOwnerNonBoardMember(): void
    {
        // 8005 is an active member but does not organise activity #9 and is not on the board, so the per-action access
        // check rejects the request even though the embedding page would have been gated separately.
        $this->authenticate(
            ['ROLE_ACTIVE_MEMBER'],
            8005,
        );
        $component = $this->overviewFor(9);

        $this->expectException(AccessDeniedException::class);
        $component->selectAll(6);
    }

    /**
     * @param string[] $roles
     */
    private function authenticate(
        array $roles,
        int $lidnr = 8025,
    ): void {
        $user = $this->entityManager->getRepository(User::class)->find($lidnr);
        self::assertInstanceOf(
            User::class,
            $user,
        );

        self::getContainer()->get('security.token_storage')->setToken(new UsernamePasswordToken(
            $user,
            'main',
            $roles,
        ));
    }

    private function overviewFor(int $activityId): SignupOverview
    {
        $component = self::getContainer()->get(SignupOverview::class);

        $activity = $this->entityManager->getRepository(Activity::class)->find($activityId);
        self::assertInstanceOf(
            Activity::class,
            $activity,
        );
        $component->activity = $activity;

        return $component;
    }

    /**
     * @return OrganiserAnnouncementEmail[]
     */
    private function bulkMessages(): array
    {
        $transport = self::getContainer()->get('messenger.transport.bulk');
        self::assertInstanceOf(
            InMemoryTransport::class,
            $transport,
        );

        $messages = [];
        foreach ($transport->getSent() as $envelope) {
            $message = $envelope->getMessage();
            if (!$message instanceof OrganiserAnnouncementEmail) {
                continue;
            }

            $messages[] = $message;
        }

        return $messages;
    }
}
