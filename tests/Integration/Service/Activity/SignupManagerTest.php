<?php

declare(strict_types=1);

namespace App\Tests\Integration\Service\Activity;

use App\Entity\Activity\Enums\ExternalSignupVerificationPurpose;
use App\Entity\Activity\Enums\SignupFieldTypes;
use App\Entity\Activity\ExternalSignup;
use App\Entity\Activity\ExternalSignupVerification;
use App\Entity\Activity\Signup;
use App\Entity\Activity\SignupList;
use App\Entity\Decision\Member;
use App\Message\Activity\ExternalSignupTokenEmail;
use App\Repository\Activity\ExternalSignupRepository;
use App\Repository\Activity\ExternalSignupVerificationRepository;
use App\Service\Activity\SignupManager;
use App\Tests\Integration\DatabaseTestCase;
use DateTimeImmutable;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\Types\Types;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;

use function count;
use function explode;
use function strval;

/**
 * Exercises the single home for the sign-up rules against a real database: how submitted answers are mapped onto value
 * rows, how a member sign-up is immediately live while an external one is born unverified behind a double-opt-in token,
 * and the verify -> confirm -> manage token lifecycle (including the resend and withdraw paths). The token emails
 * route to the in-memory transport under `when@test`, so dispatch is asserted on the queue rather than actually sent.
 */
final class SignupManagerTest extends DatabaseTestCase
{
    public function testCreateUserSignupMapsAnswersAndStartsOnTheWaitingListForALimitedList(): void
    {
        $list = $this->listWithFields();
        $member = $this->aMember();
        $answers = $this->answersFor(
            $list,
            'Vegetarian',
            1,
        );

        $signup = $this->signupManager()->createUserSignup(
            $list,
            $member,
            $answers,
        );

        self::assertSame(
            $member->getLidnr(),
            $signup->getUser()->getLidnr(),
        );
        // The list is limited, so the member starts on the waiting list until the organiser draws.
        self::assertFalse($signup->isDrawn());
        $this->assertAnswersStored(
            $signup,
            $answers,
        );
        // A member sign-up is trusted and immediately live: no verification token, no email.
        self::assertSame(
            [],
            $this->sentTokenEmails(),
        );
    }

    public function testCreateUserSignupOnAnUnlimitedListIsAdmittedImmediately(): void
    {
        $signup = $this->signupManager()->createUserSignup(
            $this->unlimitedList(),
            $this->aMember(),
            [],
        );

        self::assertTrue($signup->isDrawn());
    }

    public function testCreateUserSignupAfterALockedDrawIsAdmittedWhileCapacityRemains(): void
    {
        // Once the draw is locked, a limited list hands out its remaining places first-come-first-served: with nobody
        // admitted yet, a new member sign-up is admitted immediately instead of joining the waiting list.
        $list = $this->lockedLimitedList();

        $signup = $this->signupManager()->createUserSignup(
            $list,
            $this->aMember(),
            [],
        );

        self::assertTrue($signup->isDrawn());
    }

    public function testCreateUserSignupAfterALockedDrawIsWaitlistedWhenTheListIsFull(): void
    {
        $list = $this->lockedLimitedList();
        $this->admitExistingUpToCapacity($list);

        $signup = $this->signupManager()->createUserSignup(
            $list,
            $this->aMember(),
            [],
        );

        self::assertFalse($signup->isDrawn());
    }

    public function testOrganiserAddedExternalAfterALockedDrawIsAdmittedWhileCapacityRemains(): void
    {
        $list = $this->lockedLimitedList();

        $signup = $this->signupManager()->addExternalSignupByOrganiser(
            $list,
            'Late Guest',
            'late.guest@example.org',
            [],
        );

        self::assertTrue($signup->isDrawn());
    }

    public function testASelfServiceExternalIsOnlyAdmittedAtConfirmation(): void
    {
        $list = $this->lockedLimitedList();

        // Born unverified: even with places free the sign-up stays waitlisted -- a never-confirmed ghost must not hold
        // a place ...
        $signup = $this->signupManager()->createExternalSignup(
            $list,
            'Prompt Guest',
            'prompt.guest@example.org',
            [],
        );
        self::assertFalse($signup->isDrawn());

        // ... and confirming is the moment the first-come-first-served decision happens.
        $this->signupManager()->confirmExternalSignup($this->tokenFor(
            $signup,
            ExternalSignupVerificationPurpose::Verify,
        ));

        self::assertTrue($signup->isDrawn());
    }

    public function testASelfServiceExternalStaysWaitlistedAtConfirmationWhenTheListIsFull(): void
    {
        $list = $this->lockedLimitedList();
        $signup = $this->signupManager()->createExternalSignup(
            $list,
            'Tardy Guest',
            'tardy.guest@example.org',
            [],
        );

        $this->admitExistingUpToCapacity($list);
        $this->signupManager()->confirmExternalSignup($this->tokenFor(
            $signup,
            ExternalSignupVerificationPurpose::Verify,
        ));

        self::assertFalse($signup->isDrawn());
    }

    public function testCreateExternalSignupIsUnverifiedAndQueuesAVerifyEmail(): void
    {
        $list = $this->listWithFields();
        $answers = $this->answersFor(
            $list,
            'Halal',
            0,
        );

        $signup = $this->signupManager()->createExternalSignup(
            $list,
            'Sam External',
            'sam.external@example.org',
            $answers,
        );

        self::assertSame(
            'Sam External',
            $signup->getFullName(),
        );
        self::assertSame(
            'sam.external@example.org',
            $signup->getEmail(),
        );
        // A self sign-up, not an organiser entry.
        self::assertFalse($signup->isAddedManually());
        $this->assertAnswersStored(
            $signup,
            $answers,
        );

        // Born unverified: no participation moment yet and a live Verify token exists (so the sign-up is hidden from
        // lists, counts and admission) ...
        self::assertNull($signup->getVerifiedAt());
        self::assertTrue($this->verifications()->hasPendingVerification($signup));
        $verification = $this->tokenFor(
            $signup,
            ExternalSignupVerificationPurpose::Verify,
        );
        // ... and it expires after the one-day double-opt-in window.
        $this->assertExpiresAround(
            $verification->getExpiresAt(),
            '+1 day',
        );

        // Exactly one Verify email is queued, and its plaintext token resolves to the stored row's selector.
        self::assertSame(
            [ExternalSignupVerificationPurpose::Verify],
            $this->sentPurposes(),
        );
        self::assertSame(
            $verification->getSelector(),
            $this->selectorOf($this->sentTokenEmails()[0]),
        );
    }

    public function testAddExternalSignupByOrganiserIsImmediatelyLiveWithoutTokenOrEmail(): void
    {
        $list = $this->listWithFields();

        $signup = $this->signupManager()->addExternalSignupByOrganiser(
            $list,
            'Organiser Added',
            'organiser.added@example.org',
            $this->answersFor(
                $list,
                'None',
                2,
            ),
        );

        // The organiser vouches for the subscriber, and the sign-up is flagged as such ...
        self::assertTrue($signup->isAddedManually());
        // ... and there is no double opt-in, so no token and no confirmation e-mail; the sign-up is a participant
        // from the moment it was added.
        self::assertNotNull($signup->getVerifiedAt());
        self::assertFalse($this->verifications()->hasPendingVerification($signup));
        self::assertSame(
            [],
            $this->sentTokenEmails(),
        );
    }

    public function testConfirmExternalSignupDropsTheVerifyTokenAndIssuesAManageToken(): void
    {
        $list = $this->listWithFields();
        $signup = $this->signupManager()->createExternalSignup(
            $list,
            'Confirm Me',
            'confirm.me@example.org',
            [],
        );

        $this->signupManager()->confirmExternalSignup($this->tokenFor(
            $signup,
            ExternalSignupVerificationPurpose::Verify,
        ));

        // The double-opt-in token is gone and the confirmation moment recorded (the sign-up is now live) ...
        self::assertFalse($this->verifications()->hasPendingVerification($signup));
        self::assertNotNull($signup->getVerifiedAt());
        // ... replaced by a long-lived manage token for self-service editing.
        $manage = $this->tokenFor(
            $signup,
            ExternalSignupVerificationPurpose::Manage,
        );
        $this->assertExpiresAround(
            $manage->getExpiresAt(),
            '+1 year',
        );
        // The manage link is emailed, after the earlier verify email.
        self::assertContains(
            ExternalSignupVerificationPurpose::Manage,
            $this->sentPurposes(),
        );
    }

    public function testWithdrawRemovesTheSignupAndAllItsTokens(): void
    {
        $list = $this->listWithFields();
        $signup = $this->signupManager()->createExternalSignup(
            $list,
            'Bye Now',
            'bye.now@example.org',
            [],
        );
        $signupId = (int) $signup->getId();
        $selector = $this->tokenFor(
            $signup,
            ExternalSignupVerificationPurpose::Verify,
        )->getSelector();

        $this->signupManager()->withdraw($signup);

        self::assertNull($this->externalSignups()->find($signupId));
        self::assertNull($this->verifications()->findBySelector($selector));
    }

    public function testEditExternalSignupUpdatesAnswersInPlaceWithoutOrphaningValues(): void
    {
        $list = $this->listWithFields();
        $signup = $this->signupManager()->createExternalSignup(
            $list,
            'Before Edit',
            'edit.me@example.org',
            $this->answersFor(
                $list,
                'Vegetarian',
                0,
            ),
        );
        $valueCount = count($signup->getFieldValues());

        $updated = $this->answersFor(
            $list,
            'Pescatarian',
            2,
        );
        $this->signupManager()->editExternalSignup(
            $signup,
            'After Edit',
            $updated,
        );

        self::assertSame(
            'After Edit',
            $signup->getFullName(),
        );
        // The answers are rewritten in place: editing never adds a second value row per field.
        self::assertCount(
            $valueCount,
            $signup->getFieldValues(),
        );
        $this->assertAnswersStored(
            $signup,
            $updated,
        );
    }

    public function testResendVerificationReissuesAFreshTokenForAPendingSignup(): void
    {
        $list = $this->listWithFields();
        $signup = $this->signupManager()->createExternalSignup(
            $list,
            'Resend Me',
            'resend.me@example.org',
            [],
        );
        $originalSelector = $this->tokenFor(
            $signup,
            ExternalSignupVerificationPurpose::Verify,
        )->getSelector();

        $this->signupManager()->resendVerification(
            $list,
            'resend.me@example.org',
        );

        // The original token is dropped and a brand-new one issued (the verifier plaintext is never stored, so the old
        // link cannot be re-sent) ...
        self::assertNull($this->verifications()->findBySelector($originalSelector));
        self::assertTrue($this->verifications()->hasPendingVerification($signup));
        // ... and a second verify email is queued.
        self::assertSame(
            [
                ExternalSignupVerificationPurpose::Verify,
                ExternalSignupVerificationPurpose::Verify,
            ],
            $this->sentPurposes(),
        );
    }

    public function testResendVerificationIsSilentForAnUnknownEmail(): void
    {
        $this->signupManager()->resendVerification(
            $this->listWithFields(),
            'nobody-signed-up@example.org',
        );

        self::assertSame(
            [],
            $this->sentTokenEmails(),
        );
    }

    public function testDuplicateExternalEmailOnAListIsRejectedByTheDatabase(): void
    {
        $list = $this->listWithFields();
        $answers = $this->answersFor(
            $list,
            'Vegetarian',
            1,
        );
        $email = 'duplicate-guard@example.org';

        $this->signupManager()->createExternalSignup(
            $list,
            'First',
            $email,
            $answers,
        );

        // The (signuplist_id, email) unique index is the last line of defence against a duplicate external sign-up on
        // the same list, independent of the application-level pre-checks in the sign-up components.
        $this->expectException(UniqueConstraintViolationException::class);
        $this->signupManager()->createExternalSignup(
            $list,
            'Second',
            $email,
            $answers,
        );
    }

    public function testResendVerificationIsSilentForAnAlreadyConfirmedSignup(): void
    {
        // Alex Visitor is a seeded external sign-up with no pending verification, so it counts as already confirmed and
        // a resend request must stay silent rather than mint a token for a verified address.
        $this->signupManager()->resendVerification(
            $this->listWithFields(),
            'alex.visitor@example.org',
        );

        self::assertSame(
            [],
            $this->sentTokenEmails(),
        );
    }

    private function signupManager(): SignupManager
    {
        return self::getContainer()->get(SignupManager::class);
    }

    private function verifications(): ExternalSignupVerificationRepository
    {
        return $this->entityManager->getRepository(ExternalSignupVerification::class);
    }

    private function externalSignups(): ExternalSignupRepository
    {
        return $this->entityManager->getRepository(ExternalSignup::class);
    }

    /**
     * The seeded sign-up list that carries extra fields (a text field and a choice field), so answer mapping is
     * exercised across both a free-text and an option-reference value.
     */
    private function listWithFields(): SignupList
    {
        $list = $this->entityManager->createQueryBuilder()
            ->select('sl')
            ->from(
                SignupList::class,
                'sl',
            )
            ->where('SIZE(sl.fields) >= 2')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
        self::assertInstanceOf(
            SignupList::class,
            $list,
            'The seed is expected to contain a sign-up list with extra fields.',
        );

        return $list;
    }

    /**
     * A seeded limited-capacity list (with more sign-ups than places, all still waitlisted) whose draw is locked
     * directly in the database as of now, so the post-draw first-come-first-served rules can be exercised. The update
     * bypasses the unit of work; the loaded entity is refreshed so the sign-up paths see the lock.
     */
    private function lockedLimitedList(): SignupList
    {
        $list = $this->entityManager->createQueryBuilder()
            ->select('sl')
            ->from(
                SignupList::class,
                'sl',
            )
            ->where('sl.limitedCapacity = true')
            ->andWhere('sl.drawnAt IS NULL')
            ->andWhere('sl.capacity IS NOT NULL')
            ->orderBy(
                'sl.id',
                'ASC',
            )
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
        self::assertInstanceOf(
            SignupList::class,
            $list,
            'The seed is expected to contain an undrawn limited-capacity sign-up list.',
        );

        $this->entityManager->createQueryBuilder()
            ->update(
                SignupList::class,
                'sl',
            )
            ->set(
                'sl.drawnAt',
                ':now',
            )
            ->where('sl.id = :id')
            ->setParameter(
                'now',
                new DateTimeImmutable('now'),
                Types::DATETIME_IMMUTABLE,
            )
            ->setParameter(
                'id',
                (int) $list->getId(),
                Types::INTEGER,
            )
            ->getQuery()
            ->execute();
        $this->entityManager->refresh($list);

        return $list;
    }

    /**
     * Mark the list's earliest sign-ups as admitted, directly in the database, until its capacity is reached -- so a
     * subsequent sign-up faces a full list. The admitted-count checks query the database, so no refresh is needed.
     */
    private function admitExistingUpToCapacity(SignupList $list): void
    {
        $ids = $this->entityManager->createQueryBuilder()
            ->select('s.id')
            ->from(
                Signup::class,
                's',
            )
            ->where('s.signupList = :list')
            ->orderBy(
                's.id',
                'ASC',
            )
            ->setMaxResults((int) $list->getCapacity())
            ->setParameter(
                'list',
                (int) $list->getId(),
                Types::INTEGER,
            )
            ->getQuery()
            ->getSingleColumnResult();

        $this->entityManager->createQueryBuilder()
            ->update(
                Signup::class,
                's',
            )
            ->set(
                's.drawn',
                'true',
            )
            ->where('s.id IN (:ids)')
            ->setParameter(
                'ids',
                $ids,
            )
            ->getQuery()
            ->execute();
    }

    private function unlimitedList(): SignupList
    {
        $list = $this->entityManager->createQueryBuilder()
            ->select('sl')
            ->from(
                SignupList::class,
                'sl',
            )
            ->where('sl.limitedCapacity = :limited')
            ->setParameter(
                'limited',
                false,
            )
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
        self::assertInstanceOf(
            SignupList::class,
            $list,
            'The seed is expected to contain an unlimited sign-up list.',
        );

        return $list;
    }

    private function aMember(): Member
    {
        $member = $this->entityManager->getRepository(Member::class)->findOneBy([]);
        self::assertInstanceOf(
            Member::class,
            $member,
            'The seed is expected to contain at least one member.',
        );

        return $member;
    }

    private function tokenFor(
        ExternalSignup $signup,
        ExternalSignupVerificationPurpose $purpose,
    ): ExternalSignupVerification {
        $verification = $this->verifications()->findOneBy([
            'externalSignup' => $signup,
            'purpose' => $purpose,
        ]);
        self::assertInstanceOf(
            ExternalSignupVerification::class,
            $verification,
        );

        return $verification;
    }

    /**
     * Build the submitted-answer map (keyed by field id, as the form supplies it) for the given list: a choice field
     * takes the option id at $optionIndex, every other field type the raw $text.
     *
     * @return array<int, int|string>
     */
    private function answersFor(
        SignupList $list,
        string $text,
        int $optionIndex,
    ): array {
        $answers = [];
        foreach ($list->getFields() as $field) {
            if (SignupFieldTypes::Choice === $field->getType()) {
                $options = $field->getOptions()->getValues();
                $answers[(int) $field->getId()] = (int) $options[$optionIndex]->getId();

                continue;
            }

            $answers[(int) $field->getId()] = $text;
        }

        return $answers;
    }

    /**
     * Assert the sign-up's stored value rows mirror the submitted answers exactly as {@see SignupManager} maps them:
     * one row per field, a choice held as an option reference (no scalar) and any other type as its raw string (no
     * option).
     *
     * @param array<int, int|string> $answers
     */
    private function assertAnswersStored(
        Signup $signup,
        array $answers,
    ): void {
        self::assertCount(
            count($answers),
            $signup->getFieldValues(),
        );

        foreach ($signup->getFieldValues() as $value) {
            $submitted = $answers[(int) $value->getField()->getId()];

            if (SignupFieldTypes::Choice === $value->getField()->getType()) {
                self::assertSame(
                    $submitted,
                    $value->getOption()?->getId(),
                );
                self::assertNull($value->getValue());

                continue;
            }

            self::assertSame(
                strval($submitted),
                $value->getValue(),
            );
            self::assertNull($value->getOption());
        }
    }

    /**
     * The purposes of the token emails queued so far, in order, so a test can assert exactly which confirmation mails
     * were dispatched.
     *
     * @return ExternalSignupVerificationPurpose[]
     */
    private function sentPurposes(): array
    {
        $purposes = [];
        foreach ($this->sentTokenEmails() as $email) {
            $purposes[] = $email->getPurpose();
        }

        return $purposes;
    }

    /**
     * @return ExternalSignupTokenEmail[]
     */
    private function sentTokenEmails(): array
    {
        $transport = self::getContainer()->get('messenger.transport.high_priority');
        self::assertInstanceOf(
            InMemoryTransport::class,
            $transport,
        );

        $emails = [];
        foreach ($transport->getSent() as $envelope) {
            $message = $envelope->getMessage();
            if (!$message instanceof ExternalSignupTokenEmail) {
                continue;
            }

            $emails[] = $message;
        }

        return $emails;
    }

    private function selectorOf(ExternalSignupTokenEmail $email): string
    {
        // The plaintext token travels as `selector.verifier`; only the selector locates the stored row.
        return explode(
            '.',
            $email->getToken(),
        )[0];
    }

    private function assertExpiresAround(
        DateTimeImmutable $expiresAt,
        string $modifier,
    ): void {
        // A one-minute window absorbs the wall-clock gap between the service stamping the expiry and this assertion.
        self::assertGreaterThanOrEqual(
            new DateTimeImmutable($modifier . ' -1 minute'),
            $expiresAt,
        );
        self::assertLessThanOrEqual(
            new DateTimeImmutable($modifier . ' +1 minute'),
            $expiresAt,
        );
    }
}
