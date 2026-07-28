<?php

declare(strict_types=1);

namespace App\Command\Application;

use App\Entity\Application\Enums\Languages;
use App\Message\Application\SendNotificationDigestMessage;
use App\Repository\User\NotificationEmailSubscriptionRepository;
use App\Repository\User\PendingNotificationEmailRepository;
use App\Repository\User\UserSettingsRepository;
use App\Service\Application\NotificationSubjectResolver;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Override;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Scheduler\Attribute\AsCronTask;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatorInterface;

use function sprintf;

/**
 * For each member with queued notifications, work out which categories are due now (per that category's chosen
 * frequency), mail those in one digest, drain the sent notifications and stamp the send time. Categories that are not
 * yet due keep their notifications queued for a later run; a member who paused all email keeps nothing queued.
 *
 * The five-minute tick is jittered so it does not land on the same second as the jobs scheduled on the hour; a digest
 * is due on the hour or the day, so a delay of up to a minute changes nothing about what gets sent.
 */
#[AsCommand(
    name: 'app:notification:run-digests',
    description: 'Mail a digest to every member whose queued notifications are due.',
)]
#[AsCronTask(
    expression: '*/5 * * * *',
    jitter: 60,
)]
final class RunNotificationDigestsCommand extends Command
{
    public function __construct(
        private readonly PendingNotificationEmailRepository $pendingRepository,
        private readonly NotificationEmailSubscriptionRepository $subscriptionRepository,
        private readonly UserSettingsRepository $settingsRepository,
        private readonly NotificationSubjectResolver $subjectResolver,
        private readonly MessageBusInterface $messageBus,
        private readonly EntityManagerInterface $entityManager,
        private readonly TranslatorInterface $translator,
    ) {
        parent::__construct();
    }

    /**
     * Outgoing email is always English, so a digest entry is rendered here rather than left to the reader's locale.
     */
    private function english(TranslatableMessage $message): string
    {
        return $message->trans(
            $this->translator,
            Languages::English->getLangParam(),
        );
    }

    #[Override]
    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        $io = new SymfonyStyle(
            $input,
            $output,
        );

        $now = new DateTimeImmutable();
        $digests = 0;

        foreach ($this->pendingRepository->findUsersWithPending() as $user) {
            if ($this->settingsRepository->getOrCreateForUser($user)->getNotificationsPaused()) {
                $this->pendingRepository->deleteForUser($user);

                continue;
            }

            $dueSubscriptions = [];
            foreach ($this->subscriptionRepository->findForUser($user) as $subscription) {
                if (
                    !$subscription->getFrequency()->isDue(
                        $subscription->getLastSentAt(),
                        $now,
                    )
                ) {
                    continue;
                }

                $dueSubscriptions[$subscription->getCategory()->value] = $subscription;
            }

            if ([] === $dueSubscriptions) {
                continue;
            }

            $entries = [];
            $sentCategories = [];
            foreach ($this->pendingRepository->findForUser($user) as $queued) {
                $notification = $queued->getNotification();
                $category = $notification->getType()->value;
                if (!isset($dueSubscriptions[$category])) {
                    continue;
                }

                $type = $notification->getType();
                $subjectId = $notification->getSubjectId();
                $name = null === $subjectId
                    ? null
                    : $this->subjectResolver->nameFor(
                        $type,
                        $subjectId,
                    );

                // Whatever this was about is gone, so there is nothing left to tell them; drop it from the queue
                // rather than mailing a dead link.
                if (
                    null === $subjectId
                    || null === $name
                ) {
                    $this->entityManager->remove($queued);

                    continue;
                }

                $entries[] = [
                    'text' => $this->english($type->message($name['en'])),
                    'linkLabel' => $this->english($type->linkLabel()),
                    'type' => $type,
                    'subjectId' => $subjectId,
                ];
                $sentCategories[$category] = true;
                $this->entityManager->remove($queued);
            }

            $member = $user->getMember();
            $email = $member->getEmail();
            if (
                [] !== $entries
                && null !== $email
                && !$member->getDeleted()
                && !$member->getHidden()
                && !$member->isExpired()
            ) {
                $this->messageBus->dispatch(new SendNotificationDigestMessage(
                    $email,
                    $member->getFullName(),
                    $entries,
                ));
                ++$digests;
            }

            foreach ($dueSubscriptions as $value => $subscription) {
                if (!isset($sentCategories[$value])) {
                    continue;
                }

                $subscription->setLastSentAt($now);
            }
        }

        $this->entityManager->flush();

        $io->success(sprintf(
            'Queued %d notification digest(s).',
            $digests,
        ));

        return Command::SUCCESS;
    }
}
