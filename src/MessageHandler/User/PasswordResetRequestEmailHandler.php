<?php

declare(strict_types=1);

namespace App\MessageHandler\User;

use App\Entity\Decision\Member;
use App\Entity\User\CompanyUser;
use App\Entity\User\Enums\UserTypes;
use App\Entity\User\PasswordReset;
use App\Message\User\PasswordResetRequestEmail;
use App\Repository\Decision\MemberRepository;
use App\Repository\User\CompanyUserRepository;
use App\Repository\User\PasswordResetRepository;
use App\Util\Application\SplitToken;
use DateInterval;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use function assert;

#[AsMessageHandler]
class PasswordResetRequestEmailHandler
{
    // 16 random bytes => 32 hex chars; matches the `[0-9a-f]{32}` route requirement.
    private const int SELECTOR_BYTES = 16;

    // 32 random bytes => 64 hex chars; matches the `[0-9a-f]{64}` route requirement.
    private const int VERIFIER_BYTES = 32;
    private const int EXPIRATION_SECONDS = 60 * 15;
    private const int ACTIVE_TOKEN_REUSE_GRACE_SECONDS = 60;

    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly MemberRepository $memberRepository,
        private readonly CompanyUserRepository $companyUserRepository,
        private readonly PasswordResetRepository $passwordResetRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function __invoke(PasswordResetRequestEmail $message): void
    {
        $existingReset = null;
        $member = null;
        $companyUser = null;

        if (UserTypes::User === $message->getUserType()) {
            $membershipNumber = $message->getMembershipNumber();
            assert(null !== $membershipNumber);

            // Look up the Member directly. A User row does not need to exist yet, it will be created on confirmation
            // when no User exists (first-time activation flow for members synced from GEWISDB).
            $member = $this->memberRepository->findForReset(
                $message->getEmail(),
                $membershipNumber,
            );

            if (null !== $member) {
                $existingReset = $this->passwordResetRepository->findForMember($member);
            }
        } else {
            $companyUser = $this->companyUserRepository->loadUserByIdentifier($message->getEmail());

            if (null !== $companyUser) {
                $existingReset = $this->passwordResetRepository->findForCompanyUser($companyUser);
            }
        }

        // If a still-valid token exists with > grace period remaining, do not generate a new one and do not resend.
        // The plain verifier is not stored (only the hash), so we cannot rebuild the same email. This is a deliberate
        // tradeoff: callers see the success flash regardless, and the previously-mailed link remains usable.
        if (
            null !== $existingReset
            && !$existingReset->isExpired()
        ) {
            $minimumLifetime = new DateTimeImmutable('now')
                ->add(new DateInterval('PT' . self::ACTIVE_TOKEN_REUSE_GRACE_SECONDS . 'S'));

            if ($existingReset->getExpiresAt() > $minimumLifetime) {
                return;
            }
        }

        if (
            null === $member
            && null === $companyUser
        ) {
            // No subject found. Stay silent so we don't leak whether the email/lidnr exists. The flash in the
            // controller already shows the generic "if there is an account..." message.
            return;
        }

        $split = SplitToken::generate(
            self::SELECTOR_BYTES,
            self::VERIFIER_BYTES,
            PasswordReset::HASH_ALGO,
        );

        $expiresAt = new DateTimeImmutable('now')
            ->add(new DateInterval('PT' . self::EXPIRATION_SECONDS . 'S'));

        $passwordReset = new PasswordReset(
            $expiresAt,
            $split['selector'],
            $split['hashedToken'],
            $member,
            $companyUser,
        );

        $this->entityManager->persist($passwordReset);
        $this->entityManager->flush();

        $token = $split['token'];
        $routeName = $member instanceof Member
            ? 'user_password_reset_claim'
            : 'company_user_password_reset_claim';
        $resetUrl = $this->urlGenerator->generate(
            $routeName,
            ['token' => $token],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );

        if ($member instanceof Member) {
            $recipientEmail = $member->getEmail();
            $fullName = $member->getFullName();
        } else {
            assert($companyUser instanceof CompanyUser);
            $recipientEmail = $companyUser->getCompany()->getRepresentativeEmail();
            $fullName = $companyUser->getCompany()->getRepresentativeName();
        }

        assert(null !== $recipientEmail);

        $this->mailer->send(
            new TemplatedEmail()
            ->to($recipientEmail)
            ->subject('Your password reset request')
            ->htmlTemplate('emails/user/password-reset.html.twig')
            ->context([
                'resetUrl' => $resetUrl,
                'fullName' => $fullName,
            ]),
        );
    }
}
