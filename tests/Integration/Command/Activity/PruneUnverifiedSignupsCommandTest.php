<?php

declare(strict_types=1);

namespace App\Tests\Integration\Command\Activity;

use App\Command\Activity\PruneUnverifiedSignupsCommand;
use App\Entity\Activity\ExternalSignup;
use App\Entity\Activity\ExternalSignupVerification;
use App\Entity\Activity\SignupList;
use App\Repository\Activity\ExternalSignupRepository;
use App\Service\Activity\SignupManager;
use App\Tests\Integration\DatabaseTestCase;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * The prune cron must delete exactly the external sign-ups whose double-opt-in window lapsed without confirmation, and
 * nothing else. Pinned end to end: an unverified sign-up whose Verify token is aged past its expiry is removed, while a
 * still-pending sign-up inside its window and a confirmed sign-up (no Verify token at all) are both kept.
 */
final class PruneUnverifiedSignupsCommandTest extends DatabaseTestCase
{
    public function testDeletesExpiredUnverifiedSignupsButKeepsPendingAndConfirmedOnes(): void
    {
        $list = $this->listWithExternals();

        // An unverified sign-up whose verification window has lapsed.
        $expired = $this->signupManager()->createExternalSignup(
            $list,
            'Expired Visitor',
            'expired.visitor@example.org',
            [],
        );
        $expiredId = (int) $expired->getId();
        $this->ageVerification(
            $expiredId,
            '-1 hour',
        );

        // An unverified sign-up still inside its window.
        $pending = $this->signupManager()->createExternalSignup(
            $list,
            'Pending Visitor',
            'pending.visitor@example.org',
            [],
        );
        $pendingId = (int) $pending->getId();

        $this->runCommand();

        // Only the expired-unverified sign-up is pruned ...
        self::assertNull(
            $this->entityManager->getRepository(ExternalSignup::class)->find($expiredId),
        );
        // ... the one still within its window survives ...
        self::assertNotNull(
            $this->entityManager->getRepository(ExternalSignup::class)->find($pendingId),
        );
        // ... and the seeded, already-confirmed sign-up (no Verify token) is never touched.
        self::assertNotNull(
            $this->externalSignups()->findOneByListAndEmail(
                $list,
                'alex.visitor@example.org',
            ),
        );
    }

    private function runCommand(): void
    {
        $tester = new CommandTester(self::getContainer()->get(PruneUnverifiedSignupsCommand::class));
        $tester->execute([]);
        $tester->assertCommandIsSuccessful();
    }

    private function signupManager(): SignupManager
    {
        return self::getContainer()->get(SignupManager::class);
    }

    private function externalSignups(): ExternalSignupRepository
    {
        return $this->entityManager->getRepository(ExternalSignup::class);
    }

    /**
     * The seeded list that already carries an external sign-up (Alex Visitor), so a confirmed-and-kept assertion has a
     * real subject alongside the freshly-created unverified ones.
     */
    private function listWithExternals(): SignupList
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
            'The seed is expected to contain the Workshop sign-up list with its external sign-up.',
        );

        return $list;
    }

    /**
     * Age an external sign-up's Verify token past its expiry. The expiry is set on creation, so a DQL update (bypassing
     * the unit of work) is the way to lapse it; the prune query reads the database, so the aged value is seen at once.
     */
    private function ageVerification(
        int $externalSignupId,
        string $modifier,
    ): void {
        $this->entityManager->createQueryBuilder()
            ->update(
                ExternalSignupVerification::class,
                'v',
            )
            ->set(
                'v.expiresAt',
                ':past',
            )
            ->where('v.externalSignup = :signup')
            ->setParameter(
                'past',
                new DateTimeImmutable($modifier),
                Types::DATETIME_IMMUTABLE,
            )
            ->setParameter(
                'signup',
                $externalSignupId,
                Types::INTEGER,
            )
            ->getQuery()
            ->execute();
    }
}
