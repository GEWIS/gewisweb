<?php

declare(strict_types=1);

namespace App\Service\Activity;

use App\Entity\Activity\Enums\ExternalSignupVerificationPurpose;
use App\Entity\Activity\Enums\SignupFieldTypes;
use App\Entity\Activity\ExternalSignup;
use App\Entity\Activity\ExternalSignupVerification;
use App\Entity\Activity\Signup;
use App\Entity\Activity\SignupFieldValue;
use App\Entity\Activity\SignupList;
use App\Entity\Activity\UserSignup;
use App\Entity\Decision\Member;
use App\Message\Activity\ExternalSignupTokenEmail;
use App\Repository\Activity\ExternalSignupRepository;
use App\Repository\Activity\ExternalSignupVerificationRepository;
use DateInterval;
use DateTime;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

use function bin2hex;
use function hash;
use function random_bytes;
use function strval;

/**
 * Creates, edits, withdraws and (for externals) verifies activity sign-ups. The single home for mapping submitted form
 * answers to {@see SignupFieldValue} rows and for the external double-opt-in token lifecycle, shared by the public
 * sign-up component, the organiser's manual-add and the external self-service controller so the rules never diverge.
 *
 * Authorisation (window, GEWIS-only, ownership) stays with the callers; this service trusts what it is given.
 */
final readonly class SignupManager
{
    // 16 random bytes => 32 hex chars; 32 => 64 hex. Matches the token route requirement `[0-9a-f]{32}\.[0-9a-f]{64}`.
    private const int SELECTOR_BYTES = 16;
    private const int VERIFIER_BYTES = 32;

    // Double-opt-in window: also the deadline after which an unconfirmed sign-up is pruned.
    private const string VERIFY_TTL = 'P1D';

    // Self-service manage link: long-lived; the actual edit/unsubscribe is gated on the list still being open.
    private const string MANAGE_TTL = 'P1Y';

    public function __construct(
        private EntityManagerInterface $entityManager,
        private MessageBusInterface $messageBus,
        private ExternalSignupVerificationRepository $verificationRepository,
        private ExternalSignupRepository $externalSignupRepository,
    ) {
    }

    /**
     * Sign a member up for a list. Members are trusted, so the sign-up is immediately live.
     *
     * @param array<int, mixed> $fieldData submitted answers keyed by {@see \App\Entity\Activity\SignupField} id
     */
    public function createUserSignup(
        SignupList $signupList,
        Member $member,
        array $fieldData,
    ): UserSignup {
        $signup = new UserSignup();
        $signup->setSignupList($signupList);
        $signup->setUser($member);
        $signup->setAgreedToPolicyAt(new DateTime());
        // An unlimited list admits on sign-up; a limited list starts everyone on the waiting list until the draw.
        $signup->setDrawn(!$signupList->getLimitedCapacity());

        $this->entityManager->persist($signup);
        $this->mapFieldValues(
            $signup,
            $fieldData,
        );
        $this->entityManager->flush();

        return $signup;
    }

    /**
     * Sign an external (non-member) person up. The sign-up is created unverified (a Verify token is issued in the same
     * transaction so it is hidden from lists/counts/admission) and a confirmation e-mail is queued.
     *
     * @param array<int, mixed> $fieldData submitted answers keyed by {@see \App\Entity\Activity\SignupField} id
     */
    public function createExternalSignup(
        SignupList $signupList,
        string $fullName,
        string $email,
        array $fieldData,
    ): ExternalSignup {
        $signup = $this->buildExternalSignup(
            $signupList,
            $fullName,
            $email,
            $fieldData,
            new DateTime(),
        );

        $this->entityManager->persist($signup);
        $this->mapFieldValues(
            $signup,
            $fieldData,
        );
        $token = $this->issueToken(
            $signup,
            ExternalSignupVerificationPurpose::Verify,
            self::VERIFY_TTL,
        );
        $this->entityManager->flush();

        $this->dispatchTokenEmail(
            $signup,
            $token,
            ExternalSignupVerificationPurpose::Verify,
        );

        return $signup;
    }

    /**
     * Add an external participant on behalf of an organiser/board. No captcha and no e-mail verification: the sign-up
     * is created already-confirmed (no token row), so it is immediately live.
     *
     * @param array<int, mixed> $fieldData submitted answers keyed by {@see \App\Entity\Activity\SignupField} id
     */
    public function addExternalSignupByOrganiser(
        SignupList $signupList,
        string $fullName,
        string $email,
        array $fieldData,
    ): ExternalSignup {
        // No agreement timestamp: the participant did not themselves accept the policies.
        $signup = $this->buildExternalSignup(
            $signupList,
            $fullName,
            $email,
            $fieldData,
            null,
        );

        $this->entityManager->persist($signup);
        $this->mapFieldValues(
            $signup,
            $fieldData,
        );
        $this->entityManager->flush();

        return $signup;
    }

    /**
     * Replace a sign-up's field answers with the submitted ones (structure is frozen once a list has sign-ups, so the
     * fields themselves never change).
     *
     * @param array<int, mixed> $fieldData submitted answers keyed by {@see \App\Entity\Activity\SignupField} id
     */
    public function editSignup(
        Signup $signup,
        array $fieldData,
    ): void {
        $this->mapFieldValues(
            $signup,
            $fieldData,
        );
        $this->entityManager->flush();
    }

    /**
     * Edit an external sign-up's name and answers from the e-mailed self-service manage page. The e-mail is *not*
     * editable here: it is the address the sign-up was verified against, and changing it would bypass that verification
     * — to use a different address the participant unsubscribes and signs up again.
     *
     * @param array<int, mixed> $fieldData submitted answers keyed by {@see \App\Entity\Activity\SignupField} id
     */
    public function editExternalSignup(
        ExternalSignup $signup,
        string $fullName,
        array $fieldData,
    ): void {
        $signup->setFullName($fullName);
        $this->mapFieldValues(
            $signup,
            $fieldData,
        );
        $this->entityManager->flush();
    }

    /**
     * Withdraw a sign-up. Field values cascade-remove with the sign-up; an external's tokens are dropped first (their
     * `onDelete: CASCADE` would cover it, but the explicit delete keeps the unit of work consistent).
     */
    public function withdraw(Signup $signup): void
    {
        if ($signup instanceof ExternalSignup) {
            $this->verificationRepository->deleteAllForSignup($signup);
        }

        $this->entityManager->remove($signup);
        $this->entityManager->flush();
    }

    /**
     * Confirm an external sign-up: drop its Verify token (so it becomes live) and issue + e-mail the long-lived manage
     * token for self-service editing/unsubscribing.
     */
    public function confirmExternalSignup(ExternalSignupVerification $verification): void
    {
        $signup = $verification->getExternalSignup();

        $this->entityManager->remove($verification);
        $token = $this->issueToken(
            $signup,
            ExternalSignupVerificationPurpose::Manage,
            self::MANAGE_TTL,
        );
        $this->entityManager->flush();

        $this->dispatchTokenEmail(
            $signup,
            $token,
            ExternalSignupVerificationPurpose::Manage,
        );
    }

    /**
     * Re-issue and e-mail a fresh double-opt-in (Verify) token for a still-unverified external sign-up, for when the
     * original e-mail was lost. Mirrors the password-reset request: the sign-up existence lookup happens *here* so it
     * can run inside a message handler, off the request thread — the calling controller dispatches unconditionally, so
     * response timing never reveals whether the address is signed up. Stays silent when there is no sign-up, or it is
     * already confirmed (no Verify token). The verifier plaintext is never stored, so the old token is dropped and a
     * fresh one issued; this also resets the expiry, giving the sign-up a fresh prune window.
     */
    public function resendVerification(
        SignupList $signupList,
        string $email,
    ): void {
        $signup = $this->externalSignupRepository->findOneByListAndEmail(
            $signupList,
            $email,
        );
        if (
            null === $signup
            || !$this->verificationRepository->hasPendingVerification($signup)
        ) {
            return;
        }

        // A pending sign-up only ever holds Verify tokens, so dropping them all and issuing a fresh one is safe.
        $this->verificationRepository->deleteAllForSignup($signup);
        $token = $this->issueToken(
            $signup,
            ExternalSignupVerificationPurpose::Verify,
            self::VERIFY_TTL,
        );
        $this->entityManager->flush();

        $this->dispatchTokenEmail(
            $signup,
            $token,
            ExternalSignupVerificationPurpose::Verify,
        );
    }

    /**
     * @param array<int, mixed> $fieldData submitted answers keyed by {@see \App\Entity\Activity\SignupField} id
     */
    private function buildExternalSignup(
        SignupList $signupList,
        string $fullName,
        string $email,
        array $fieldData,
        ?DateTime $agreedAt,
    ): ExternalSignup {
        $signup = new ExternalSignup();
        $signup->setSignupList($signupList);
        $signup->setFullName($fullName);
        $signup->setEmail($email);
        $signup->setAgreedToPolicyAt($agreedAt);
        // An unlimited list admits on sign-up; a limited list starts everyone on the waiting list until the draw.
        $signup->setDrawn(!$signupList->getLimitedCapacity());

        return $signup;
    }

    /**
     * Map submitted answers onto {@see SignupFieldValue} rows, updating existing rows in place (so editing never
     * orphans values). Storage mirrors {@see SignupFieldValue::displayValue()}/{@see Signup::toFormArray()}: yes/no is
     * the literal 'Yes'/'No', a choice is the option reference, text/number are the raw string.
     *
     * @param array<int, mixed> $fieldData
     */
    private function mapFieldValues(
        Signup $signup,
        array $fieldData,
    ): void {
        $existing = [];
        foreach ($signup->getFieldValues() as $fieldValue) {
            $existing[$fieldValue->getField()->getId()] = $fieldValue;
        }

        foreach ($signup->getSignupList()->getFields() as $field) {
            $fieldValue = $existing[$field->getId()] ?? null;
            if (null === $fieldValue) {
                $fieldValue = new SignupFieldValue();
                $fieldValue->setField($field);
                $fieldValue->setSignup($signup);
                $signup->getFieldValues()->add($fieldValue);
            }

            $fieldValue->setValue(null);
            $fieldValue->setOption(null);

            $submitted = $fieldData[$field->getId()] ?? null;

            switch ($field->getType()) {
                case SignupFieldTypes::Choice:
                    foreach ($field->getOptions() as $option) {
                        if ($option->getId() === (int) $submitted) {
                            $fieldValue->setOption($option);

                            break;
                        }
                    }

                    break;
                case SignupFieldTypes::YesNo:
                    $fieldValue->setValue('1' === strval($submitted) ? 'Yes' : 'No');

                    break;
                default:
                    // Text and Number are both stored as their raw string.
                    $fieldValue->setValue(null === $submitted ? null : strval($submitted));
            }
        }
    }

    /**
     * Generate a `selector.verifier` token, persist its hash as a verification row of the given purpose, and return the
     * plaintext token for the e-mail. Caller flushes.
     */
    private function issueToken(
        ExternalSignup $signup,
        ExternalSignupVerificationPurpose $purpose,
        string $ttl,
    ): string {
        $selector = bin2hex(random_bytes(self::SELECTOR_BYTES));
        $verifier = bin2hex(random_bytes(self::VERIFIER_BYTES));
        $hashedToken = hash(
            ExternalSignupVerification::HASH_ALGO,
            $verifier,
        );

        $verification = new ExternalSignupVerification(
            $signup,
            $purpose,
            $selector,
            $hashedToken,
            new DateTimeImmutable('now')->add(new DateInterval($ttl)),
        );
        $this->entityManager->persist($verification);

        return $selector . '.' . $verifier;
    }

    private function dispatchTokenEmail(
        ExternalSignup $signup,
        string $token,
        ExternalSignupVerificationPurpose $purpose,
    ): void {
        $this->messageBus->dispatch(
            new ExternalSignupTokenEmail(
                (int) $signup->getId(),
                $token,
                $purpose,
            ),
        );
    }
}
