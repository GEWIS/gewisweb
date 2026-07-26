<?php

declare(strict_types=1);

namespace App\Twig\Components\Notification;

use App\Entity\Application\Enums\Languages;
use App\Entity\Application\Notification;
use App\Entity\User\Enums\UserRoles;
use App\Entity\User\User;
use App\Repository\Application\NotificationRepository;
use App\Repository\User\UserSettingsRepository;
use App\Service\Application\NotificationSubjectResolver;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\DefaultActionTrait;

use function array_filter;
use function count;

/**
 * The navbar notification centre for members: a bell with an unread badge and a dropdown of the most recent
 * notifications. Unread is the number of notifications created since the member last marked the centre read; marking
 * read stamps that moment on their settings, so one notification row serves every member.
 */
#[AsLiveComponent(
    name: 'Notification:Bell',
    template: 'components/Notification/Bell.html.twig',
)]
#[IsGranted(UserRoles::User->value)]
class Bell
{
    use DefaultActionTrait;

    private const int LIMIT = 10;

    /**
     * Notifications older than this are never shown or counted, however long ago the member last read the centre.
     */
    private const string WINDOW = '-30 days';

    /**
     * The recent-notifications query is the same for every member, so it is result-cached for this long. The bell is
     * eventually consistent within this window; a brand-new notification still toasts in real time over SSE.
     */
    private const int RESULT_CACHE_TTL = 60;

    /** @var list<array{notification: Notification, name: string}>|null */
    private ?array $entries = null;

    private bool $readAtLoaded = false;

    private ?DateTimeImmutable $readAt = null;

    public function __construct(
        private readonly NotificationRepository $notificationRepository,
        private readonly NotificationSubjectResolver $subjectResolver,
        private readonly UserSettingsRepository $settingsRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly Security $security,
    ) {
    }

    /**
     * The notifications to show, each paired with its subject's name in the language being read. A notification whose
     * subject has since been removed has nothing left to say and is dropped here, so the badge can never count more
     * than the dropdown lists.
     *
     * @return list<array{notification: Notification, name: string}>
     */
    public function getEntries(): array
    {
        if (null !== $this->entries) {
            return $this->entries;
        }

        $notifications = $this->notificationRepository->findRecent(
            $this->windowStart(),
            self::LIMIT,
            self::RESULT_CACHE_TTL,
        );

        $names = $this->subjectResolver->resolveNames($notifications);
        $language = Languages::current();

        $entries = [];
        foreach ($notifications as $notification) {
            $id = $notification->getId();
            if (
                null === $id
                || !isset($names[$id])
            ) {
                continue;
            }

            $entries[] = [
                'notification' => $notification,
                'name' => match ($language) {
                    Languages::English => $names[$id]['en'],
                    Languages::Dutch => $names[$id]['nl'],
                },
            ];
        }

        return $this->entries = $entries;
    }

    /**
     * The unread count is derived from the notifications already loaded rather than a second query. The badge only
     * distinguishes the exact numbers up to nine, so counting the recent window (which never returns more than
     * {@see LIMIT}) is enough: ten or more simply reads as "9+".
     */
    public function getUnreadCount(): int
    {
        $readAt = $this->getReadAt();

        return count(array_filter(
            $this->getEntries(),
            static fn (array $entry): bool => null === $readAt
                || $entry['notification']->getCreatedAt() > $readAt,
        ));
    }

    public function getReadAt(): ?DateTimeImmutable
    {
        if (!$this->readAtLoaded) {
            $this->readAt = $this->currentUser()?->getSettings()?->getNotificationsReadAt();
            $this->readAtLoaded = true;
        }

        return $this->readAt;
    }

    #[LiveAction]
    public function markAllRead(): void
    {
        $user = $this->currentUser();
        if (null === $user) {
            return;
        }

        $this->settingsRepository->getOrCreateForUser($user)
            ->setNotificationsReadAt(new DateTimeImmutable());
        $this->entityManager->flush();

        $this->readAtLoaded = false;
    }

    /**
     * Floored to the hour so the same result-cache entry serves every request within it.
     */
    private function windowStart(): DateTimeImmutable
    {
        $cutoff = new DateTimeImmutable(self::WINDOW);

        return $cutoff->setTime(
            (int) $cutoff->format('H'),
            0,
        );
    }

    private function currentUser(): ?User
    {
        $user = $this->security->getUser();

        return $user instanceof User
            ? $user
            : null;
    }
}
