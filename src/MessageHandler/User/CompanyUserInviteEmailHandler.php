<?php

declare(strict_types=1);

namespace App\MessageHandler\User;

use App\Entity\Application\Enums\Languages;
use App\Message\User\CompanyUserInviteEmail;
use App\Repository\User\CompanyUserInviteRepository;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Sends the invitation link. Runs in a worker; the email is always English. The invitation is re-loaded by id and
 * silently skipped if it has since been withdrawn or already accepted.
 */
#[AsMessageHandler]
class CompanyUserInviteEmailHandler
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly CompanyUserInviteRepository $inviteRepository,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function __invoke(CompanyUserInviteEmail $message): void
    {
        $invite = $this->inviteRepository->find($message->getInviteId());
        if (null === $invite) {
            return;
        }

        $url = $this->urlGenerator->generate(
            'company_user_invite_accept',
            [
                'token' => $message->getToken(),
                '_locale' => Languages::English->getLangParam(),
            ],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );

        $this->mailer->send(
            new TemplatedEmail()
                ->to($invite->getEmail())
                ->subject('You have been invited to the GEWIS career portal')
                ->htmlTemplate('emails/career/company-user-invite.html.twig')
                ->context([
                    'fullName' => $invite->getName(),
                    'companyName' => $invite->getCompany()->getName(),
                    'inviteUrl' => $url,
                    'expiresAt' => $invite->getExpiresAt(),
                ]),
        );
    }
}
