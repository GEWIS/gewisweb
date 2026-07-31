<?php

declare(strict_types=1);

namespace App\Twig\Components\Notification;

use App\Entity\Application\Enums\Languages;
use App\Entity\Application\Notification;
use App\Entity\User\Enums\UserRoles;
use App\Entity\User\NotificationInteraction;
use App\Entity\User\User;
use App\Repository\Application\NotificationRepository;
use App\Repository\User\NotificationInteractionRepository;
use App\Repository\User\UserSettingsRepository;
use App\Security\User\Firewall;
use App\Service\Application\NotificationContextResolver;
use App\Service\Application\NotificationSubjectResolver;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
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

    /** @var list<array{notification: Notification, name: string, href: string, read: bool}>|null */
    private ?array $entries = null;

    private bool $readAtLoaded = false;

    private ?DateTimeImmutable $readAt = null;

    public function __construct(
        private readonly NotificationRepository $notificationRepository,
        private readonly NotificationSubjectResolver $subjectResolver,
        private readonly NotificationContextResolver $contextResolver,
        private readonly UserSettingsRepository $settingsRepository,
        private readonly NotificationInteractionRepository $interactionRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly Security $security,
    ) {
    }

    /**
     * The notifications to show, each paired with the name it reads by and the link it points at. A notification whose
     * subject has since been removed has nothing left to say and is dropped here, so the badge can never count more
     * than the dropdown lists. One carrying its own frozen label is never dropped, having no subject to lose.
     *
     * The link is built here rather than in the template, so it can be told which firewall the reader is on. The
     * component only ever renders for members, so that is always the main one.
     *
     * @return list<array{notification: Notification, name: string, href: string, read: bool}>
     */
    public function getEntries(): array
    {
        if (null !== $this->entries) {
            return $this->entries;
        }

        $user = $this->currentUser();
        if (null === $user) {
            return $this->entries = [];
        }

        $notifications = $this->notificationRepository->findRecentFor(
            $this->windowStart(),
            $user,
            self::LIMIT,
        );

        $names = $this->subjectResolver->resolveNames($notifications);
        $interactions = $this->interactionRepository->findForNotifications(
            $user,
            $notifications,
        );
        $readAt = $this->getReadAt();
        $language = Languages::current();

        $entries = [];
        foreach ($notifications as $notification) {
            $id = $notification->getId();
            $context = $notification->getContext();
            $name = null === $context
                ? null
                : $this->contextResolver->resolve(
                    $notification->getType(),
                    $context,
                    $language,
                );

            if (null === $name) {
                if (
                    null === $id
                    || !isset($names[$id])
                ) {
                    continue;
                }

                $name = match ($language) {
                    Languages::English => $names[$id]['en'],
                    Languages::Dutch => $names[$id]['nl'],
                };
            }

            $type = $notification->getType();

            $entries[] = [
                'notification' => $notification,
                'name' => $name,
                'href' => $this->urlGenerator->generate(
                    $type->route(Firewall::Main),
                    $type->routeParameters($notification->getSubjectId()),
                ),
                'read' => (null !== $readAt && $notification->getCreatedAt() <= $readAt)
                    || (null !== $id && null !== ($interactions[$id] ?? null)?->getReadAt()),
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
        return count(array_filter(
            $this->getEntries(),
            static fn (array $entry): bool => !$entry['read'],
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

    /**
     * Read one notification without touching the rest.
     */
    #[LiveAction]
    public function markRead(#[LiveArg]
    int $notification,): void
    {
        $this->interact(
            $notification,
            static function (NotificationInteraction $interaction): void {
                $interaction->setReadAt(new DateTimeImmutable());
            },
        );
    }

    /**
     * Clear one notification away. The notification itself stays put, because most of them belong to everybody; it is
     * only hidden from this member.
     */
    #[LiveAction]
    public function dismiss(#[LiveArg]
    int $notification,): void
    {
        $this->interact(
            $notification,
            static function (NotificationInteraction $interaction): void {
                $now = new DateTimeImmutable();
                $interaction->setDismissedAt($now);
                $interaction->setReadAt($interaction->getReadAt() ?? $now);
            },
        );
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
     * Apply a change to what this member has done with one notification.
     *
     * Only notifications currently on show can be acted on, so a crafted id cannot reach one that was never theirs to
     * see.
     *
     * @param callable(NotificationInteraction): void $change
     */
    private function interact(
        int $notificationId,
        callable $change,
    ): void {
        $user = $this->currentUser();
        if (null === $user) {
            return;
        }

        foreach ($this->getEntries() as $entry) {
            if ($entry['notification']->getId() !== $notificationId) {
                continue;
            }

            $change($this->interactionRepository->getOrCreate(
                $user,
                $entry['notification'],
            ));
            $this->entityManager->flush();
            $this->entries = null;

            return;
        }
    }

    private function windowStart(): DateTimeImmutable
    {
        return new DateTimeImmutable(self::WINDOW);
    }

    private function currentUser(): ?User
    {
        $user = $this->security->getUser();

        return $user instanceof User
            ? $user
            : null;
    }
}
