<?php

declare(strict_types=1);

namespace App\Service\Application;

use App\Entity\Application\Enums\Languages;
use App\Entity\Application\Enums\NotificationType;
use App\Entity\Application\Notification;
use Override;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The website channel: pushes the notification as a real-time toast to everyone online. It reaches every member and is
 * always on; the persisted row is what the notification centre shows to anyone who was offline.
 *
 * The toast carries its text and its link in both languages, because the browser has no translation runtime and picks
 * whichever matches its locale.
 */
final readonly class RealtimeNotificationChannel implements NotificationChannelInterface
{
    public function __construct(
        private RealtimeNotifier $realtimeNotifier,
        private NotificationSubjectResolver $subjectResolver,
        private TranslatorInterface $translator,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    #[Override]
    public function deliver(Notification $notification): void
    {
        $type = $notification->getType();
        $subjectId = $notification->getSubjectId();
        if (null === $subjectId) {
            return;
        }

        $name = $this->subjectResolver->nameFor(
            $type,
            $subjectId,
        );
        if (null === $name) {
            return;
        }

        $this->realtimeNotifier->toPublic(new RealtimePayload(
            $notification->getLevel(),
            [
                'en' => $this->translate(
                    $type->message($name['en']),
                    Languages::English,
                ),
                'nl' => $this->translate(
                    $type->message($name['nl']),
                    Languages::Dutch,
                ),
            ],
            link: $this->link(
                $type,
                $subjectId,
            ),
            notificationId: $notification->getId(),
        ));
    }

    /**
     * @return array{href: array{en: string, nl: string}, label: array{en: string, nl: string}}
     */
    private function link(
        NotificationType $type,
        int $subjectId,
    ): array {
        return [
            'href' => [
                'en' => $this->url(
                    $type,
                    $subjectId,
                    Languages::English,
                ),
                'nl' => $this->url(
                    $type,
                    $subjectId,
                    Languages::Dutch,
                ),
            ],
            'label' => [
                'en' => $this->translate(
                    $type->linkLabel(),
                    Languages::English,
                ),
                'nl' => $this->translate(
                    $type->linkLabel(),
                    Languages::Dutch,
                ),
            ],
        ];
    }

    private function translate(
        TranslatableMessage $message,
        Languages $language,
    ): string {
        return $message->trans(
            $this->translator,
            $language->getLangParam(),
        );
    }

    private function url(
        NotificationType $type,
        int $subjectId,
        Languages $language,
    ): string {
        return $this->urlGenerator->generate(
            $type->route(),
            $type->routeParameters($subjectId) + ['_locale' => $language->getLangParam()],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );
    }
}
