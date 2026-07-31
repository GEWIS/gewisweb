<?php

declare(strict_types=1);

namespace App\Command\Activity;

use App\Entity\Activity\SignupList;
use App\Entity\Activity\UserSignup;
use App\Entity\Application\Enums\Languages;
use App\Entity\Application\Enums\NotificationType;
use App\Repository\Activity\SignupListRepository;
use App\Repository\User\UserRepository;
use App\Service\Application\NotificationPublisher;
use DateInterval;
use DateTime;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Override;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Scheduler\Attribute\AsCronTask;

use function count;
use function sprintf;
use function strval;

/**
 * Warn everybody on a sign-up list that it is about to close, while they can still act on it.
 *
 * Withdrawing is impossible once a list closes, so a member who has changed their mind has until then and no longer.
 * The reminder is a nudge rather than news, so it stays on the website: one closing list can concern a hundred people
 * and none of them asked to be emailed about it.
 *
 * Hourly rather than by the minute: a reminder a little either side of a day ahead is the same reminder, and a list is
 * marked as reminded so a member hears about it once.
 */
#[AsCommand(
    name: 'app:activity:remind-closing-signups',
    description: 'Tell everybody on a sign-up list that it is about to close.',
)]
#[AsCronTask(
    expression: '17 * * * *',
    jitter: 300,
)]
final class RemindClosingSignupsCommand extends Command
{
    /**
     * How far ahead of closing the reminder goes out.
     */
    private const string LEAD_TIME = 'PT24H';

    public function __construct(
        private readonly SignupListRepository $signupListRepository,
        private readonly UserRepository $userRepository,
        private readonly NotificationPublisher $publisher,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    #[Override]
    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        $ui = new SymfonyStyle(
            $input,
            $output,
        );

        $now = new DateTime();
        $until = new DateTime()->add(new DateInterval(self::LEAD_TIME));

        $lists = $this->signupListRepository->findClosingSoon(
            $now,
            $until,
        );

        if ([] === $lists) {
            $ui->success('No sign-up lists are closing soon.');

            return Command::SUCCESS;
        }

        $reminded = 0;
        foreach ($lists as $list) {
            $reminded += $this->remind($list);
            $list->setRemindedAt(new DateTimeImmutable());
        }

        $this->entityManager->flush();

        $ui->success(sprintf(
            'Reminded %d member(s) about %d closing sign-up list(s).',
            $reminded,
            count($lists),
        ));

        return Command::SUCCESS;
    }

    /**
     * Externals have no account to show anything in, and are already served by the manage link they were emailed.
     */
    private function remind(SignupList $list): int
    {
        $activityId = $list->getActivity()->getId();
        if (null === $activityId) {
            return 0;
        }

        $type = $list->getFields()->isEmpty()
            ? NotificationType::SignupClosing
            : NotificationType::SignupClosingWithFields;

        $context = [
            'activity' => strval($activityId),
            'list' => $list->getName()->getText(Languages::English) ?? '',
        ];

        $reminded = 0;
        foreach ($list->getSignUps() as $signup) {
            if (!$signup instanceof UserSignup) {
                continue;
            }

            $user = $this->userRepository->find($signup->getUser()->getLidnr());
            if (null === $user) {
                continue;
            }

            $this->publisher->publishFor(
                $user,
                $type,
                $context,
            );
            ++$reminded;
        }

        return $reminded;
    }
}
