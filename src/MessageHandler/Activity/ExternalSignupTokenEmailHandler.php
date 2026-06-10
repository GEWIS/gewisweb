<?php

declare(strict_types=1);

namespace App\MessageHandler\Activity;

use App\Entity\Application\Enums\Languages;
use App\Message\Activity\ExternalSignupTokenEmail;
use App\Repository\Activity\ExternalSignupRepository;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use function sprintf;

/**
 * Sends an external participant the link for a sign-up token. Runs in a worker; the e-mail is always English (the
 * sender's/request locale says nothing about what the recipient reads), so the activity name prefers its English text.
 * The sign-up is re-loaded by id and silently skipped if it has since been withdrawn.
 */
#[AsMessageHandler]
class ExternalSignupTokenEmailHandler
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly ExternalSignupRepository $externalSignupRepository,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function __invoke(ExternalSignupTokenEmail $message): void
    {
        $signup = $this->externalSignupRepository->find($message->getExternalSignupId());
        if (null === $signup) {
            return;
        }

        $activityName = $signup->getSignupList()->getActivity()->getName()->getText(Languages::English) ?? '';
        $signupListName = $signup->getSignupList()->getName()->getText(Languages::English) ?? '';
        $purpose = $message->getPurpose();

        $url = $this->urlGenerator->generate(
            $purpose->routeName(),
            ['token' => $message->getToken()],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );

        $subject = sprintf(
            $purpose->subjectFormat(),
            $activityName,
            $signupListName,
        );

        $this->mailer->send(
            new TemplatedEmail()
                ->to($signup->getEmail())
                ->subject($subject)
                ->htmlTemplate($purpose->emailTemplate())
                ->context([
                    'name' => $signup->getFullName(),
                    'activityName' => $activityName,
                    'signupListName' => $signupListName,
                    'url' => $url,
                ]),
        );
    }
}
