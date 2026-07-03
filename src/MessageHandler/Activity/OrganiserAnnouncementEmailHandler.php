<?php

declare(strict_types=1);

namespace App\MessageHandler\Activity;

use App\Message\Activity\OrganiserAnnouncementEmail;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Address;

#[AsMessageHandler]
class OrganiserAnnouncementEmailHandler
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(OrganiserAnnouncementEmail $message): void
    {
        $replyTo = $message->getReplyTo();

        // One personalised email per recipient (no shared To/BCC, so nobody sees the others, and the greeting is by
        // name). A single recipient failing (a bad address, a transient transport error) is logged and skipped, not
        // thrown: throwing would make Messenger retry the whole message and re-send to everyone already mailed.
        foreach ($message->getRecipients() as $recipient) {
            $email = new TemplatedEmail()
                ->to(new Address(
                    $recipient['email'],
                    $recipient['name'],
                ))
                ->subject($message->getSubject())
                ->htmlTemplate('emails/activity/organiser-announcement.html.twig')
                ->replyTo($replyTo)
                ->context([
                    'subject' => $message->getSubject(),
                    'body' => $message->getBody(),
                    'activityName' => $message->getActivityName(),
                    'name' => $recipient['name'],
                    'organiserEmail' => $replyTo,
                ]);

            try {
                $this->mailer->send($email);
            } catch (TransportExceptionInterface $exception) {
                $this->logger->error(
                    'Failed to send an activity sign-up bulk email to a recipient.',
                    ['exception' => $exception],
                );
            }
        }
    }
}
