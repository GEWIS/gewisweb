<?php

declare(strict_types=1);

namespace App\MessageHandler\Decision;

use App\Entity\Decision\Authorization;
use App\Entity\Decision\Enums\MeetingTypes;
use App\Entity\Decision\Member;
use App\Message\Decision\AuthorizationCreatedEmail;
use App\Message\Decision\AuthorizationRevokedEmail;
use App\Repository\Decision\AuthorizationRepository;
use App\Repository\Decision\MeetingRepository;
use NumberFormatter;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

use function sprintf;

/**
 * Sends the GMM authorization mails. Outgoing mail is always English, so the ordinal meeting number is formatted with
 * a fixed English locale.
 */
class AuthorizationEmailHandler
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly AuthorizationRepository $authorizationRepository,
        private readonly MeetingRepository $meetingRepository,
    ) {
    }

    #[AsMessageHandler]
    public function handleCreated(AuthorizationCreatedEmail $message): void
    {
        $authorization = $this->authorizationRepository->find($message->authorizationId);

        if (null === $authorization) {
            return;
        }

        $context = $this->context($authorization);

        $granteeEmail = $authorization->getRecipient()->getEmail();
        if (null !== $granteeEmail) {
            $this->mailer->send(
                new TemplatedEmail()
                ->to($granteeEmail)
                ->subject(sprintf(
                    'GMM authorisation from %s',
                    $authorization->getAuthorizer()->getFullName(),
                ))
                ->htmlTemplate('emails/decision/authorisation-grantee.html.twig')
                ->context($context),
            );
        }

        $grantorEmail = $authorization->getAuthorizer()->getEmail();
        if (null === $grantorEmail) {
            return;
        }

        $this->mailer->send(
            new TemplatedEmail()
            ->to($grantorEmail)
            ->subject(sprintf(
                'GMM authorisation for %s',
                $authorization->getRecipient()->getFullName(),
            ))
            ->htmlTemplate('emails/decision/authorisation-grantor.html.twig')
            ->context($context),
        );
    }

    #[AsMessageHandler]
    public function handleRevoked(AuthorizationRevokedEmail $message): void
    {
        $authorization = $this->authorizationRepository->find($message->authorizationId);

        if (null === $authorization) {
            return;
        }

        $granteeEmail = $authorization->getRecipient()->getEmail();
        if (null === $granteeEmail) {
            return;
        }

        $this->mailer->send(
            new TemplatedEmail()
            ->to($granteeEmail)
            ->subject(sprintf(
                'GMM authorisation from %s revoked',
                $authorization->getAuthorizer()->getFullName(),
            ))
            ->htmlTemplate('emails/decision/authorisation-revoked.html.twig')
            ->context($this->context($authorization)),
        );
    }

    /**
     * @return array{grantor: Member, grantee: Member, meetingNumber: string, meetingDate: string}
     */
    private function context(Authorization $authorization): array
    {
        $meeting = $this->meetingRepository->findMeeting(
            MeetingTypes::ALV,
            $authorization->getMeetingNumber(),
        );

        $formatter = new NumberFormatter(
            'en_GB',
            NumberFormatter::ORDINAL,
        );

        $meetingDate = '';
        if (null !== $meeting) {
            $meetingDate = $meeting->getDate()->format('F j, Y');
        }

        return [
            'grantor' => $authorization->getAuthorizer(),
            'grantee' => $authorization->getRecipient(),
            'meetingNumber' => (string) $formatter->format($authorization->getMeetingNumber()),
            'meetingDate' => $meetingDate,
        ];
    }
}
