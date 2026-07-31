<?php

declare(strict_types=1);

namespace App\Service\Application;

use App\Entity\Application\Enums\Languages;
use App\Entity\Application\Enums\NotificationType;
use App\Entity\Application\Notification;
use App\Security\User\Firewall;
use Override;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatorInterface;

use function strval;

/**
 * The website channel: pushes the notification as a real-time toast to whoever it concerns, which for most kinds is
 * everyone online and otherwise is the one user it is addressed to. It is always on; the persisted row is what the
 * notification centre shows to anyone who was offline.
 *
 * The toast carries its text and its link in both languages, because the browser has no translation runtime and picks
 * whichever matches its locale.
 */
final readonly class RealtimeNotificationChannel implements NotificationChannelInterface
{
    public function __construct(
        private RealtimeNotifier $realtimeNotifier,
        private NotificationSubjectResolver $subjectResolver,
        private NotificationContextResolver $contextResolver,
        private TranslatorInterface $translator,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    #[Override]
    public function deliver(Notification $notification): void
    {
        $type = $notification->getType();
        $subjectId = $notification->getSubjectId();
        $name = $this->name(
            $type,
            $subjectId,
            $notification->getContext(),
        );
        if (null === $name) {
            return;
        }

        // Topics are per account, so there is nowhere to push something addressed to a role. It waits in the
        // notification centre instead, which is where a queue of work belongs anyway.
        if (null !== $notification->getRecipientRole()) {
            return;
        }

        $recipientUser = $notification->getRecipientUser();
        $recipientCompanyUser = $notification->getRecipientCompanyUser();

        $recipient = match (true) {
            null !== $recipientUser => Firewall::Main,
            null !== $recipientCompanyUser => Firewall::Company,
            default => null,
        };

        $payload = new RealtimePayload(
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
                $recipient,
                $notification->getContext() ?? [],
            ),
            notificationId: $notification->getId(),
        );

        if (null !== $recipient) {
            $this->realtimeNotifier->toUser(
                $recipient->value,
                strval($recipientUser?->getUserIdentifier() ?? $recipientCompanyUser?->getUserIdentifier()),
                $payload,
            );

            return;
        }

        $this->realtimeNotifier->toPublic($payload);
    }

    /**
     * What the notification reads by, in both languages. A subject that has since gone leaves nothing to announce.
     *
     * @param array<string, string>|null $context
     *
     * @return array{en: string, nl: string}|null
     */
    private function name(
        NotificationType $type,
        ?int $subjectId,
        ?array $context,
    ): ?array {
        if (null !== $context) {
            $english = $this->contextResolver->resolve(
                $type,
                $context,
                Languages::English,
            );
            $dutch = $this->contextResolver->resolve(
                $type,
                $context,
                Languages::Dutch,
            );

            if (
                null === $english
                || null === $dutch
            ) {
                return null;
            }

            return [
                'en' => $english,
                'nl' => $dutch,
            ];
        }

        if (null === $subjectId) {
            return null;
        }

        return $this->subjectResolver->nameFor(
            $type,
            $subjectId,
        );
    }

    /**
     * @param array<string, string> $context
     *
     * @return array{href: array{en: string, nl: string}, label: array{en: string, nl: string}}
     */
    private function link(
        NotificationType $type,
        ?int $subjectId,
        ?Firewall $recipient,
        array $context,
    ): array {
        return [
            'href' => [
                'en' => $this->url(
                    $type,
                    $subjectId,
                    $recipient,
                    $context,
                    Languages::English,
                ),
                'nl' => $this->url(
                    $type,
                    $subjectId,
                    $recipient,
                    $context,
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

    /**
     * @param array<string, string> $context
     */
    private function url(
        NotificationType $type,
        ?int $subjectId,
        ?Firewall $recipient,
        array $context,
        Languages $language,
    ): string {
        return $this->urlGenerator->generate(
            $type->route($recipient),
            $type->routeParameters(
                $subjectId,
                $context,
            ) + ['_locale' => $language->getLangParam()],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );
    }
}
