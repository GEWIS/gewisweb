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
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\DefaultActionTrait;

use function array_column;
use function array_map;
use function array_slice;
use function array_sum;
use function count;
use function explode;
use function in_array;
use function intval;

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

    /**
     * How many lines the dropdown shows. A run of the same kind counts as one, so more rows than this are read to
     * fill it.
     */
    private const int LIMIT = 10;

    /**
     * How many notifications are read to build those lines. Anything beyond this is out of reach until the ones in
     * front of it are cleared away, which is what the limit already meant.
     */
    private const int FETCH = 50;

    /**
     * Notifications older than this are never shown or counted, however long ago the member last read the centre.
     */
    private const string WINDOW = '-30 days';

    /** @var list<array{notification: Notification, name: string, href: string, unread: int, ids: list<int>}>|null */
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
        private readonly RoleHierarchyInterface $roleHierarchy,
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
     * A run of the same kind is shown as one line, since ten separate lines saying an activity was submitted is a
     * worse answer to "what happened" than one saying ten were.
     *
     * @return list<array{notification: Notification, name: string, href: string, unread: int, ids: list<int>}>
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
            $this->rolesOf($user),
            self::FETCH,
        );

        $names = $this->subjectResolver->resolveNames($notifications);
        $interactions = $this->interactionRepository->findForNotifications(
            $user,
            $notifications,
        );
        $readAt = $this->getReadAt();
        $language = Languages::current();

        $entries = [];
        $current = null;
        $currentType = null;

        foreach ($notifications as $notification) {
            $id = $notification->getId();
            if (null === $id) {
                continue;
            }

            $context = $notification->getContext();
            $name = null === $context
                ? null
                : $this->contextResolver->resolve(
                    $notification->getType(),
                    $context,
                    $language,
                );

            if (null === $name) {
                if (!isset($names[$id])) {
                    continue;
                }

                $name = match ($language) {
                    Languages::English => $names[$id]['en'],
                    Languages::Dutch => $names[$id]['nl'],
                };
            }

            $unread = (null === $readAt || $notification->getCreatedAt() > $readAt)
                && null === ($interactions[$id] ?? null)?->getReadAt();
            $type = $notification->getType();

            if (
                null !== $current
                && $type === $currentType
            ) {
                $current['ids'][] = $id;
                $current['unread'] += $unread
                    ? 1
                    : 0;

                continue;
            }

            if (null !== $current) {
                $entries[] = $current;
            }

            $currentType = $type;
            $current = [
                'notification' => $notification,
                'name' => $name,
                'href' => $this->urlGenerator->generate(
                    $type->route(Firewall::Main),
                    $type->routeParameters($notification->getSubjectId()),
                ),
                'unread' => $unread ? 1 : 0,
                'ids' => [$id],
            ];
        }

        if (null !== $current) {
            $entries[] = $current;
        }

        return $this->entries = $this->withGroupLinks(array_slice(
            $entries,
            0,
            self::LIMIT,
        ));
    }

    /**
     * Counted from what is already loaded rather than with a second query, and counting notifications rather than
     * lines: a line standing for three unread ones is three. The badge only distinguishes numbers up to nine, so what
     * is in the window is enough.
     */
    public function getUnreadCount(): int
    {
        return array_sum(array_column(
            $this->getEntries(),
            'unread',
        ));
    }

    /**
     * A line standing for several points at the list they all belong to, since no single one of them is what the
     * reader is after.
     *
     * @param list<array{notification: Notification, name: string, href: string, unread: int, ids: list<int>}> $entries
     *
     * @return list<array{notification: Notification, name: string, href: string, unread: int, ids: list<int>}>
     */
    private function withGroupLinks(array $entries): array
    {
        $linked = [];
        foreach ($entries as $entry) {
            if (count($entry['ids']) > 1) {
                $entry['href'] = $this->urlGenerator->generate(
                    $entry['notification']->getType()->manyRoute(Firewall::Main),
                );
            }

            $linked[] = $entry;
        }

        return $linked;
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
     * Read what is behind one line without touching the rest.
     */
    #[LiveAction]
    public function markRead(#[LiveArg]
    string $notifications,): void
    {
        $this->interact(
            $notifications,
            static function (NotificationInteraction $interaction): void {
                $interaction->setReadAt(new DateTimeImmutable());
            },
        );
    }

    /**
     * Clear a line away. The notifications themselves stay put, because most of them belong to everybody; they are
     * only hidden from this member.
     */
    #[LiveAction]
    public function dismiss(#[LiveArg]
    string $notifications,): void
    {
        $this->interact(
            $notifications,
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
     * Apply a change to everything behind one line, which is several notifications when they are shown as a run.
     *
     * Only what is currently on show can be acted on, so an id that was never theirs to see reaches nothing.
     *
     * @param callable(NotificationInteraction): void $change
     */
    private function interact(
        string $notificationIds,
        callable $change,
    ): void {
        $user = $this->currentUser();
        if (null === $user) {
            return;
        }

        $wanted = array_map(
            intval(...),
            explode(
                ',',
                $notificationIds,
            ),
        );

        $touched = false;
        foreach ($this->getEntries() as $entry) {
            foreach (null === $entry['notification']->getId() ? [] : $entry['ids'] as $id) {
                if (
                    !in_array(
                        $id,
                        $wanted,
                        true,
                    )
                ) {
                    continue;
                }

                $notification = $this->notificationRepository->find($id);
                if (null === $notification) {
                    continue;
                }

                $change($this->interactionRepository->getOrCreate(
                    $user,
                    $notification,
                ));
                $touched = true;
            }
        }

        if (!$touched) {
            return;
        }

        $this->entityManager->flush();
        $this->entries = null;
    }

    private function windowStart(): DateTimeImmutable
    {
        return new DateTimeImmutable(self::WINDOW);
    }

    /**
     * Every role the viewer holds, hierarchy included. Security hands these out as names, and the hierarchy holds a
     * couple that are not roles anything can be addressed to, which drop out here.
     *
     * @return list<UserRoles>
     */
    private function rolesOf(User $user): array
    {
        $roles = [];

        foreach ($this->roleHierarchy->getReachableRoleNames($user->getRoles()) as $name) {
            $role = UserRoles::tryFrom($name);

            if (null === $role) {
                continue;
            }

            $roles[] = $role;
        }

        return $roles;
    }

    private function currentUser(): ?User
    {
        $user = $this->security->getUser();

        return $user instanceof User
            ? $user
            : null;
    }
}
