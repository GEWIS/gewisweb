<?php

declare(strict_types=1);

namespace App\MessageHandler\Career;

use App\Entity\Application\Enums\RevisionStatus;
use App\Message\Career\CareerReviewDecisionEmail;
use App\Repository\Career\CompanyRepository;
use App\Repository\User\CompanyUserRepository;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use function sprintf;

/**
 * Emails a company's representatives what the committee decided. Runs in a worker; the email is always English.
 *
 * Everybody who can still act for the company is written to, not only whoever put the change forward: the one who
 * submitted it may be away, and a decision is the company's business rather than one person's.
 */
#[AsMessageHandler]
class CareerReviewDecisionEmailHandler
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly CompanyRepository $companyRepository,
        private readonly CompanyUserRepository $companyUserRepository,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function __invoke(CareerReviewDecisionEmail $message): void
    {
        $company = $this->companyRepository->find($message->getCompanyId());
        if (null === $company) {
            return;
        }

        $outcome = $message->getOutcome();
        $subject = sprintf(
            '%s: %s',
            $message->getSubjectName(),
            match ($outcome) {
                RevisionStatus::Approved => 'approved',
                RevisionStatus::ChangesRequested => 'changes requested',
                default => 'not approved',
            },
        );

        $url = $this->urlGenerator->generate(
            $message->isVacancy()
                ? 'company/vacancies'
                : 'company/profile/status',
            ['_locale' => 'en'],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );

        // Somebody who has been shut out is no longer part of this.
        foreach ($this->companyUserRepository->findActiveForCompany($company) as $companyUser) {
            $this->mailer->send(
                new TemplatedEmail()
                    ->to($companyUser->getEmail())
                    ->subject($subject)
                    ->htmlTemplate('emails/career/review-decision.html.twig')
                    ->context([
                        'fullName' => $companyUser->getName(),
                        'companyName' => $company->getName(),
                        'subjectName' => $message->getSubjectName(),
                        'isVacancy' => $message->isVacancy(),
                        'outcome' => $outcome->value,
                        'portalUrl' => $url,
                    ]),
            );
        }
    }
}
