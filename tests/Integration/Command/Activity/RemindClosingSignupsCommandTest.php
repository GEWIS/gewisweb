<?php

declare(strict_types=1);

namespace App\Tests\Integration\Command\Activity;

use App\Entity\Activity\Signup;
use App\Entity\Activity\SignupList;
use App\Entity\Activity\UserSignup;
use App\Entity\Application\Enums\NotificationType;
use App\Entity\Application\Notification;
use App\Repository\Application\NotificationRepository;
use App\Tests\Integration\DatabaseTestCase;
use DateTime;
use Doctrine\DBAL\Types\Types;

use function array_filter;
use function count;

/**
 * The reminder is the last chance a member has to withdraw, so what matters is that everybody on the list hears about
 * it exactly once and that a list nowhere near closing stays quiet.
 */
final class RemindClosingSignupsCommandTest extends DatabaseTestCase
{
    /**
     * Externals are not counted: they have no account to show anything in, and are served by the manage link they were
     * emailed instead.
     */
    public function testEveryMemberOnAListClosingSoonIsToldOnce(): void
    {
        $list = $this->aListClosingIn('+6 hours');
        $subscribers = count(array_filter(
            $list->getSignUps()->toArray(),
            static fn (Signup $signup): bool => $signup instanceof UserSignup,
        ));
        self::assertGreaterThan(
            0,
            $subscribers,
            'The seed is expected to contain a sign-up list with members on it.',
        );

        $this->executeCommand();

        self::assertCount(
            $subscribers,
            $this->reminders(),
        );
        self::assertNotNull($list->getRemindedAt());
    }

    public function testRunningAgainSaysNothingFurther(): void
    {
        $this->aListClosingIn('+6 hours');

        $this->executeCommand();
        $after = count($this->reminders());
        $this->executeCommand();

        self::assertCount(
            $after,
            $this->reminders(),
        );
    }

    public function testAListThatIsNotClosingYetIsLeftAlone(): void
    {
        $this->aListClosingIn('+9 days');

        $this->executeCommand();

        self::assertSame(
            [],
            $this->reminders(),
        );
    }

    /**
     * The wording differs by whether there are answers to change, so the kind has to follow the list's own fields.
     */
    public function testTheKindFollowsWhetherTheListHasFields(): void
    {
        $list = $this->aListClosingIn('+6 hours');
        $expected = $list->getFields()->isEmpty()
            ? NotificationType::SignupClosing
            : NotificationType::SignupClosingWithFields;

        $this->executeCommand();

        self::assertSame(
            $expected,
            $this->reminders()[0]->getType(),
        );
    }

    /**
     * Every list but the one under test is pushed well out of the window, so the seed's own lists cannot answer for it.
     */
    private function aListClosingIn(string $offset): SignupList
    {
        $this->entityManager->createQueryBuilder()
            ->update(
                SignupList::class,
                'sl',
            )
            ->set(
                'sl.closeDate',
                ':far',
            )
            ->setParameter(
                'far',
                new DateTime('+1 year'),
                Types::DATETIME_MUTABLE,
            )
            ->getQuery()
            ->execute();

        $list = $this->entityManager->createQueryBuilder()
            ->select('sl')
            ->from(
                SignupList::class,
                'sl',
            )
            ->join(
                'sl.revision',
                'r',
            )
            ->join(
                'r.activity',
                'a',
            )
            ->where('a.liveRevision = r')
            ->andWhere('a.cancelledAt IS NULL')
            ->andWhere('a.unpublishedAt IS NULL')
            ->andWhere('SIZE(sl.signUps) > 0')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
        self::assertInstanceOf(
            SignupList::class,
            $list,
            'The seed is expected to contain a live sign-up list with subscribers.',
        );

        $list->setOpenDate(new DateTime('-1 week'));
        $list->setCloseDate(new DateTime($offset));
        $list->setRemindedAt(null);
        $this->entityManager->flush();

        return $list;
    }

    /**
     * @return Notification[]
     */
    private function reminders(): array
    {
        $this->entityManager->clear();

        return self::getContainer()->get(NotificationRepository::class)->findBy([
            'type' => [
                NotificationType::SignupClosing,
                NotificationType::SignupClosingWithFields,
            ],
        ]);
    }

    private function executeCommand(): void
    {
        $this->assertCommandIsSuccessful(static::runCommand('app:activity:remind-closing-signups'));
    }
}
