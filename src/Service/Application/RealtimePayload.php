<?php

declare(strict_types=1);

namespace App\Service\Application;

use App\Entity\Application\Enums\AlertTypes;
use App\Entity\Application\Enums\RealtimeEventType;

/**
 * A toast envelope. Text is carried per language because the client has no translation runtime; it picks the language
 * matching its locale.
 *
 * @psalm-type LocalisedString = array{en: string, nl: string}
 * @psalm-type RealtimeLink = array{href: LocalisedString, label: LocalisedString}
 */
final readonly class RealtimePayload
{
    /**
     * @param LocalisedString      $message
     * @param LocalisedString|null $title
     * @param RealtimeLink|null    $link
     */
    public function __construct(
        private AlertTypes $level,
        private array $message,
        private ?array $title = null,
        private ?array $link = null,
        private ?int $notificationId = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'type' => RealtimeEventType::Toast->value,
            'level' => $this->level->value,
            'message' => $this->message,
        ];

        if (null !== $this->title) {
            $data['title'] = $this->title;
        }

        if (null !== $this->link) {
            $data['link'] = $this->link;
        }

        if (null !== $this->notificationId) {
            $data['notificationId'] = $this->notificationId;
        }

        return $data;
    }
}
