<?php

declare(strict_types=1);

namespace App\MessageHandler\Application;

use App\Message\Application\SendNotificationDigestMessage;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Exception\RfcComplianceException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use function count;

#[AsMessageHandler]
class SendNotificationDigestHandler
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(SendNotificationDigestMessage $message): void
    {
        $entries = $message->getEntries();
        if ([] === $entries) {
            return;
        }

        $settingsUrl = $this->urlGenerator->generate(
            'user_settings_notifications',
            ['_locale' => 'en'],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );

        $subject = 1 === count($entries)
            ? $entries[0]['text']
            : 'You have new notifications from GEWIS';

        try {
            $this->mailer->send(
                new TemplatedEmail()
                    ->to($message->getEmail())
                    ->subject($subject)
                    ->htmlTemplate('emails/notification/digest.html.twig')
                    ->context([
                        'fullName' => $message->getName(),
                        'entries' => $entries,
                        'settingsUrl' => $settingsUrl,
                    ]),
            );
        } catch (TransportExceptionInterface | RfcComplianceException $e) {
            $this->logger->warning(
                'Failed to send a notification digest email.',
                ['exception' => $e],
            );
        }
    }
}
