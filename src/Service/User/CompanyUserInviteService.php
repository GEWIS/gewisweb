<?php

declare(strict_types=1);

namespace App\Service\User;

use App\Entity\Career\Company;
use App\Entity\Career\Enums\CompanyAuditVerbs;
use App\Entity\User\CompanyUser;
use App\Entity\User\CompanyUserInvite;
use App\Entity\User\User;
use App\Message\User\CompanyUserInviteEmail;
use App\Repository\User\CompanyUserInviteRepository;
use App\Repository\User\CompanyUserRepository;
use App\Service\Career\CompanyAuditLogger;
use App\Util\Application\SplitToken;
use DateInterval;
use DateTime;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Invites somebody to represent a company, and turns an accepted invitation into an account.
 *
 * An address can belong to at most one representative, so an invitation is refused when an account or another pending
 * invitation already claims it. Inviting the same address twice reissues the existing invitation rather than leaving
 * two links in the wild.
 */
final readonly class CompanyUserInviteService
{
    // 16 random bytes => 32 hex chars; matches the `[0-9a-f]{32}` route requirement.
    private const int SELECTOR_BYTES = 16;

    // 32 random bytes => 64 hex chars; matches the `[0-9a-f]{64}` route requirement.
    private const int VERIFIER_BYTES = 32;
    private const string LIFETIME = 'P7D';

    public function __construct(
        private EntityManagerInterface $entityManager,
        private CompanyUserInviteRepository $inviteRepository,
        private CompanyUserRepository $companyUserRepository,
        private MessageBusInterface $messageBus,
        private UserPasswordHasherInterface $passwordHasher,
        private CompanyAuditLogger $auditLogger,
        private Security $security,
    ) {
    }

    /**
     * @throws RuntimeException when the address is already spoken for.
     */
    public function invite(
        Company $company,
        string $email,
        string $name,
        ?User $invitedBy,
    ): CompanyUserInvite {
        if (null !== $this->companyUserRepository->loadUserByIdentifier($email)) {
            throw new RuntimeException('That email address already belongs to a representative.');
        }

        $existing = $this->inviteRepository->findByEmail($email);
        if (null !== $existing) {
            if ($existing->getCompany() !== $company) {
                throw new RuntimeException('That email address is already invited by another company.');
            }

            $this->resend($existing);

            return $existing;
        }

        $split = $this->generateToken();
        $invite = new CompanyUserInvite(
            $company,
            $email,
            $name,
            $invitedBy,
            $split['selector'],
            $split['hashedToken'],
            $this->expiry(),
        );

        $this->entityManager->persist($invite);
        $this->auditLogger->log(
            $company,
            $invitedBy,
            CompanyAuditVerbs::RepresentativeInvited,
            $email,
        );
        $this->entityManager->flush();

        $this->dispatchEmail(
            $invite,
            $split['token'],
        );

        return $invite;
    }

    /**
     * Issues a fresh link for a pending invitation and invalidates the previous one, since only the hash of a token is
     * kept and the old link cannot be rebuilt.
     */
    public function resend(CompanyUserInvite $invite): void
    {
        $split = $this->generateToken();
        $invite->reissue(
            $split['selector'],
            $split['hashedToken'],
            $this->expiry(),
        );

        $this->auditLogger->log(
            $invite->getCompany(),
            $this->actor(),
            CompanyAuditVerbs::InviteResent,
            $invite->getEmail(),
        );
        $this->entityManager->flush();

        $this->dispatchEmail(
            $invite,
            $split['token'],
        );
    }

    public function revoke(CompanyUserInvite $invite): void
    {
        $this->auditLogger->log(
            $invite->getCompany(),
            $this->actor(),
            CompanyAuditVerbs::InviteRevoked,
            $invite->getEmail(),
        );

        $this->entityManager->remove($invite);
        $this->entityManager->flush();
    }

    /**
     * Turns the invitation into an account with the password its holder just chose. Signing them in is left to the
     * caller, which is the only place that has a request to sign them in on.
     */
    public function accept(
        CompanyUserInvite $invite,
        string $plainPassword,
    ): CompanyUser {
        $companyUser = new CompanyUser();
        $companyUser->setCompany($invite->getCompany());
        $companyUser->setEmail($invite->getEmail());
        $companyUser->setName($invite->getName());
        $companyUser->setPassword($this->passwordHasher->hashPassword(
            $companyUser,
            $plainPassword,
        ));
        $companyUser->setPasswordChangedOn(new DateTime());

        $this->entityManager->persist($companyUser);
        $this->entityManager->remove($invite);
        $this->auditLogger->log(
            $invite->getCompany(),
            $companyUser,
            CompanyAuditVerbs::RepresentativeJoined,
            $companyUser->getName(),
        );
        $this->entityManager->flush();

        return $companyUser;
    }

    /**
     * @return array{selector: string, hashedToken: string, token: string}
     */
    private function generateToken(): array
    {
        return SplitToken::generate(
            self::SELECTOR_BYTES,
            self::VERIFIER_BYTES,
            CompanyUserInvite::HASH_ALGO,
        );
    }

    private function expiry(): DateTimeImmutable
    {
        return new DateTimeImmutable('now')->add(new DateInterval(self::LIFETIME));
    }

    private function actor(): ?User
    {
        $user = $this->security->getUser();

        return $user instanceof User
            ? $user
            : null;
    }

    private function dispatchEmail(
        CompanyUserInvite $invite,
        string $token,
    ): void {
        $this->messageBus->dispatch(new CompanyUserInviteEmail(
            (int) $invite->getId(),
            $token,
        ));
    }
}
