<?php

declare(strict_types=1);

namespace App\MessageHandler\User;

use App\Entity\Application\Enums\AlertTypes;
use App\Entity\Application\Enums\Languages;
use App\Entity\User\CompanyUser;
use App\Entity\User\User;
use App\Message\User\SecurityNotificationMessage;
use App\Repository\User\CompanyUserRepository;
use App\Repository\User\UserRepository;
use App\Security\User\Firewall;
use App\Service\Application\NotificationContextResolver;
use App\Service\Application\NotificationPublisher;
use DateTimeInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Exception\RfcComplianceException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Tells whoever an account belongs to that something happened to it: in the notification centre, as a toast to whatever
 * they have open, and by email.
 *
 * None of this is something to opt into. It goes out however the member has set their notification preferences,
 * including while they have everything paused, because a message nobody asked for is the whole point of it.
 *
 * The notification is published first. Publishing does not throw (a channel that fails is logged and skipped), so the
 * durable record always lands; the mail is then allowed to fail quietly rather than retry. A retry would run this
 * handler again and leave a second warning in the notification centre, which reads far worse than an email that did
 * not arrive.
 */
#[AsMessageHandler]
class SecurityNotificationHandler
{
    public function __construct(
        private readonly NotificationPublisher $publisher,
        private readonly UserRepository $userRepository,
        private readonly CompanyUserRepository $companyUserRepository,
        private readonly MailerInterface $mailer,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly NotificationContextResolver $contextResolver,
        private readonly TranslatorInterface $translator,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(SecurityNotificationMessage $message): void
    {
        $firewall = Firewall::tryFrom($message->getFirewallName());
        if (null === $firewall) {
            return;
        }

        $account = $this->account(
            $firewall,
            $message->getUserIdentifier(),
        );

        // The account is gone, so there is nobody left to tell.
        if (null === $account) {
            return;
        }

        $type = $message->getType();
        $origin = $message->getOrigin();

        $this->publisher->publishFor(
            $account,
            $type,
            $origin,
            AlertTypes::Warning,
        );

        $subject = $type->emailSubject();
        $recipient = self::mailbox($account);

        if (
            null === $subject
            || null === $recipient
        ) {
            return;
        }

        $device = $this->contextResolver->resolve(
            $type,
            $origin,
            Languages::English,
        ) ?? '';

        try {
            $this->mailer->send(
                new TemplatedEmail()
                    ->to($recipient['email'])
                    ->subject($subject)
                    ->htmlTemplate('emails/user/security-notification.html.twig')
                    ->context([
                        'fullName' => $recipient['name'],
                        'headline' => $subject,
                        'summary' => $type->message($device)->trans(
                            $this->translator,
                            Languages::English->getLangParam(),
                        ),
                        'occurredAt' => $message->getOccurredAt()->format(DateTimeInterface::ATOM),
                        'resetUrl' => $this->urlGenerator->generate(
                            $firewall->forgotPasswordRoute(),
                            ['_locale' => Languages::English->getLangParam()],
                            UrlGeneratorInterface::ABSOLUTE_URL,
                        ),
                    ]),
            );
        } catch (TransportExceptionInterface | RfcComplianceException $e) {
            $this->logger->warning(
                'Failed to send a security notification email.',
                [
                    'type' => $type->value,
                    'exception' => $e,
                ],
            );
        }
    }

    private function account(
        Firewall $firewall,
        string $userIdentifier,
    ): User|CompanyUser|null {
        return match ($firewall) {
            Firewall::Main => $this->userRepository->find((int) $userIdentifier),
            Firewall::Company => $this->companyUserRepository->find((int) $userIdentifier),
        };
    }

    /**
     * A member always has an address (they could not have signed in otherwise), a company writes to whoever
     * represents it.
     *
     * @return array{email: string, name: string}|null
     */
    private static function mailbox(User|CompanyUser $account): ?array
    {
        if ($account instanceof User) {
            $email = $account->getMember()->getEmail();
            $name = $account->getMember()->getFullName();
        } else {
            $email = $account->getCompany()->getRepresentativeEmail();
            $name = $account->getCompany()->getRepresentativeName();
        }

        if (null === $email) {
            return null;
        }

        return [
            'email' => $email,
            'name' => $name,
        ];
    }
}
