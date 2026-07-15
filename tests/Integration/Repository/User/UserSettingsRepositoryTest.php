<?php

declare(strict_types=1);

namespace App\Tests\Integration\Repository\User;

use App\Repository\User\UserRepository;
use App\Repository\User\UserSettingsRepository;
use App\Tests\Integration\DatabaseTestCase;

/**
 * The settings row is created on demand, keyed by the member's lidnr (derived identity), and reused thereafter, so a
 * member never ends up with two settings rows.
 */
final class UserSettingsRepositoryTest extends DatabaseTestCase
{
    private const int MEMBER = 8030;

    public function testGetOrCreateReturnsADefaultsRowAndReusesItAfterFlush(): void
    {
        $repository = self::getContainer()->get(UserSettingsRepository::class);
        $user = self::getContainer()->get(UserRepository::class)->find(self::MEMBER);
        self::assertNotNull($user);

        // No row exists in the seed, so the first call builds one with all-false defaults.
        self::assertNull($repository->find(self::MEMBER));

        $created = $repository->getOrCreateForUser($user);
        self::assertFalse($created->getPhotoTaggingOptOut());
        self::assertFalse($created->getHideYearOfBirth());

        $this->entityManager->flush();

        // The primary key is the lidnr, and a second call returns the very same row rather than a duplicate.
        self::assertSame(
            self::MEMBER,
            $repository->find(self::MEMBER)?->getUser()->getLidnr(),
        );
        self::assertSame(
            $created,
            $repository->getOrCreateForUser($user),
        );
    }
}
