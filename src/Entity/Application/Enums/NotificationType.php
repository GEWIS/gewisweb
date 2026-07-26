<?php

declare(strict_types=1);

namespace App\Entity\Application\Enums;

use Symfony\Component\Translation\TranslatableMessage;

/**
 * The kind of event a persisted {@see \App\Entity\Application\Notification} records. Drives the icon shown in the
 * notification centre. It also holds everything the notification says: the sentence, where it points and what the
 * link reads, so a notification row only has to record its subject.
 */
enum NotificationType: string
{
    case AlbumPublished = 'album_published';
    case ActivityPublished = 'activity_published';

    public function icon(): string
    {
        return match ($this) {
            self::AlbumPublished => 'fa-images',
            self::ActivityPublished => 'fa-calendar-day',
        };
    }

    /**
     * The route a notification of this kind points at. Callers build the URL themselves, so the notification centre
     * links within the language being read instead of a language frozen when the notification was created.
     */
    public function route(): string
    {
        return match ($this) {
            self::AlbumPublished => 'photo/album',
            self::ActivityPublished => 'activity/view',
        };
    }

    /**
     * @return array<string, int|string>
     */
    public function routeParameters(int $subjectId): array
    {
        return match ($this) {
            self::AlbumPublished => [
                'type' => 'album',
                'album' => $subjectId,
            ],
            self::ActivityPublished => ['activity' => $subjectId],
        };
    }

    /**
     * What the link to the subject reads.
     */
    public function linkLabel(): TranslatableMessage
    {
        return match ($this) {
            self::AlbumPublished => new TranslatableMessage('View album'),
            self::ActivityPublished => new TranslatableMessage('View activity'),
        };
    }

    /**
     * The notification itself, with the subject's name filled in.
     */
    public function message(string $name): TranslatableMessage
    {
        return match ($this) {
            self::AlbumPublished => new TranslatableMessage(
                'A new photo album "%name%" is online.',
                ['%name%' => $name],
            ),
            self::ActivityPublished => new TranslatableMessage(
                'A new activity "%name%" has been published.',
                ['%name%' => $name],
            ),
        };
    }
}
