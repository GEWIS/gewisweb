<?php

declare(strict_types=1);

namespace App\Service\Application;

use App\Entity\Application\Enums\Languages;
use App\Entity\Application\Enums\NotificationType;
use App\Entity\Application\Notification;
use App\Repository\Activity\ActivityRepository;
use App\Repository\Photo\AlbumRepository;

use function array_values;

/**
 * Looks up the names of the subjects notifications point at. Notifications store only a key, so the notification
 * centre, the toasts and the digest emails all come through here to turn that key back into something readable.
 *
 * Lookups are grouped per type, so showing a feed costs one query per kind of notification rather than one per row. A
 * subject that has since been removed simply drops out of the result and its notification is not shown.
 */
final readonly class NotificationSubjectResolver
{
    public function __construct(
        private AlbumRepository $albumRepository,
        private ActivityRepository $activityRepository,
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
        return match ($type) {
            NotificationType::AlbumPublished => $this->albumNames($subjectIds),
            NotificationType::ActivityPublished => $this->activityNames($subjectIds),
        };
    }

    /**
     * @param int[] $subjectIds
     *
     * @return array<int, array{en: string, nl: string}>
     */
    private function albumNames(array $subjectIds): array
    {
        $names = [];
        foreach ($this->albumRepository->findBy(['id' => $subjectIds]) as $album) {
            $id = $album->getId();
            if (null === $id) {
                continue;
            }

            $name = $album->getName();
            $names[$id] = [
                'en' => $name,
                'nl' => $name,
            ];
        }

        return $names;
    }

    /**
     * @param int[] $subjectIds
     *
     * @return array<int, array{en: string, nl: string}>
     */
    private function activityNames(array $subjectIds): array
    {
        $names = [];
        foreach ($this->activityRepository->findBy(['id' => $subjectIds]) as $activity) {
            $id = $activity->getId();
            if (null === $id) {
                continue;
            }

            $name = $activity->getName();
            $names[$id] = [
                'en' => $name->getText(Languages::English) ?? '',
                'nl' => $name->getText(Languages::Dutch) ?? '',
            ];
        }

        return $names;
    }
}
