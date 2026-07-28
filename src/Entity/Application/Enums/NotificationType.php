<?php

declare(strict_types=1);

namespace App\Entity\Application\Enums;

use Override;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The kind of event a persisted {@see \App\Entity\Application\Notification} records. Drives the icon shown in the
 * notification centre and, as a category, the per-member email opt-in. It also holds everything the notification says:
 * the sentence, where it points and what the link reads, so a notification row only has to record its subject.
 */
enum NotificationType: string implements TranslatableInterface
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

    /**
     * A short line under the category title on the notification settings page, explaining when it fires.
     */
    public function hint(): TranslatableMessage
    {
        return match ($this) {
            self::AlbumPublished => new TranslatableMessage('When photos of an event are published'),
            self::ActivityPublished => new TranslatableMessage('New activities you can sign up for'),
        };
    }

    #[Override]
    public function trans(
        TranslatorInterface $translator,
        ?string $locale = null,
    ): string {
        return match ($this) {
            self::AlbumPublished => $translator->trans(
                'New photo albums',
                locale: $locale,
            ),
            self::ActivityPublished => $translator->trans(
                'New activities',
                locale: $locale,
            ),
        };
    }
}
