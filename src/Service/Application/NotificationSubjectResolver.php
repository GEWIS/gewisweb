<?php

declare(strict_types=1);

namespace App\Service\Application;

use App\Entity\Application\Enums\NotificationType;
use App\Entity\Application\Notification;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

use function array_values;

/**
 * Looks up the names of the subjects notifications point at. Notifications store only a key, so the notification
 * centre, the toasts and the digest emails all come through here to turn that key back into something readable.
 *
 * What a key names is the domain's business, so each module registers a {@see NotificationSubjectNamerInterface} and
 * this only decides who to ask. A kind nobody answers for has no subject to name, which is the right answer for the
 * notifications that stand on their own.
 *
 * Lookups are grouped per type, so showing a feed costs one query per kind of notification rather than one per row. A
 * subject that has since been removed simply drops out of the result and its notification is not shown.
 */
final readonly class NotificationSubjectResolver
{
    /**
     * @param iterable<NotificationSubjectNamerInterface> $namers
     */
    public function __construct(
        #[AutowireIterator('app.notification_subject_namer')]
        private iterable $namers,
    ) {
    }

    /**
     * @return array{en: string, nl: string}|null
     */
    public function nameFor(
        NotificationType $type,
        int $subjectId,
    ): ?array {
        return $this->namesFor(
            $type,
            [$subjectId],
        )[$subjectId] ?? null;
    }

    /**
     * The names of these notifications' subjects, keyed by notification id.
     *
     * @param Notification[] $notifications
     *
     * @return array<int, array{en: string, nl: string}>
     */
    public function resolveNames(array $notifications): array
    {
        $resolved = [];
        foreach (NotificationType::cases() as $type) {
            $subjectIds = [];
            foreach ($notifications as $notification) {
                $id = $notification->getId();
                $subjectId = $notification->getSubjectId();
                if (
                    null === $id
                    || null === $subjectId
                    || $type !== $notification->getType()
                ) {
                    continue;
                }

                $subjectIds[$id] = $subjectId;
            }

            if ([] === $subjectIds) {
                continue;
            }

            $names = $this->namesFor(
                $type,
                array_values($subjectIds),
            );
            foreach ($subjectIds as $id => $subjectId) {
                if (!isset($names[$subjectId])) {
                    continue;
                }

                $resolved[$id] = $names[$subjectId];
            }
        }

        return $resolved;
    }

    /**
     * @param int[] $subjectIds
     *
     * @return array<int, array{en: string, nl: string}>
     */
    private function namesFor(
        NotificationType $type,
        array $subjectIds,
    ): array {
        foreach ($this->namers as $namer) {
            if (!$namer->supports($type)) {
                continue;
            }

            return $namer->namesFor(
                $type,
                $subjectIds,
            );
        }

        return [];
    }
}
