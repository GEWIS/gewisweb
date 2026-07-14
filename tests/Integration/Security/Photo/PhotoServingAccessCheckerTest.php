<?php

declare(strict_types=1);

namespace App\Tests\Integration\Security\Photo;

use App\DataFixtures\Photo\PhotoFixture;
use App\Entity\Application\Enums\StorageNamespace;
use App\Entity\User\Enums\UserRoles;
use App\Entity\User\User;
use App\Repository\Photo\MemberTagRepository;
use App\Security\Photo\PhotoServingAccessChecker;
use App\Tests\Integration\DatabaseTestCase;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * The serving gate for the private photos namespace. A full member is granted based on the (already
 * validated) signature without any per-photo query, while a graduate's request runs the per-photo voter so a leaked
 * URL can never bypass their #1658 membership cutoff.
 */
final class PhotoServingAccessCheckerTest extends DatabaseTestCase
{
    public function testFullMemberIsGrantedWithoutAPerPhotoLookup(): void
    {
        $this->authenticate(
            8030,
            [UserRoles::Member->value],
        );

        // The path need not resolve to a real photo. The member fast path short-circuits before any query.
        self::assertTrue(
            $this->checker()->isGranted(
                'photos/albums/ab/whatever.jpg',
                StorageNamespace::PhotoOriginal,
            ),
        );
    }

    public function testGraduateTaggedInTheSubtreeIsGranted(): void
    {
        $this->pinMembershipEnd(
            PhotoFixture::GRADUATE_TAGGED_IN_SUBTREE,
            '2020-01-01 00:00:00',
        );
        $path = $this->photoTaggingGraduate(PhotoFixture::GRADUATE_TAGGED_IN_SUBTREE);
        $this->authenticate(
            PhotoFixture::GRADUATE_TAGGED_IN_SUBTREE,
            [UserRoles::Graduate->value],
        );

        self::assertTrue($this->checker()->isGranted($path, StorageNamespace::PhotoOriginal));
    }

    public function testGraduateNotTaggedIsDeniedEvenWithAValidUrl(): void
    {
        // The leaked-URL case: a valid signed URL for a photo the graduate is not tagged in, made after their
        // membership ended, must still be denied.
        $this->pinMembershipEnd(
            PhotoFixture::GRADUATE_TAGGED_NOWHERE,
            '2020-01-01 00:00:00',
        );
        $path = $this->photoTaggingGraduate(PhotoFixture::GRADUATE_TAGGED_IN_SUBTREE);
        $this->authenticate(
            PhotoFixture::GRADUATE_TAGGED_NOWHERE,
            [UserRoles::Graduate->value],
        );

        self::assertFalse($this->checker()->isGranted($path, StorageNamespace::PhotoOriginal));
    }

    public function testAnonymousIsDenied(): void
    {
        $path = $this->photoTaggingGraduate(PhotoFixture::GRADUATE_TAGGED_IN_SUBTREE);

        self::assertFalse($this->checker()->isGranted($path, StorageNamespace::PhotoOriginal));
    }

    private function checker(): PhotoServingAccessChecker
    {
        return self::getContainer()->get(PhotoServingAccessChecker::class);
    }

    private function photoTaggingGraduate(int $lidnr): string
    {
        $tags = self::getContainer()->get(MemberTagRepository::class)->getTagsByLidnr($lidnr);
        self::assertNotEmpty(
            $tags,
            'The graduate is expected to be tagged in the seed.',
        );

        return $tags[0]->getPhoto()->getPath();
    }

    private function pinMembershipEnd(
        int $lidnr,
        string $endsOn,
    ): void {
        $this->entityManager->getConnection()->update(
            'Member',
            ['membershipEndsOn' => $endsOn],
            ['lidnr' => $lidnr],
        );
        $this->entityManager->clear();
    }

    /**
     * @param list<string> $roles
     */
    private function authenticate(
        int $lidnr,
        array $roles,
    ): void {
        $user = $this->entityManager->getRepository(User::class)->find($lidnr);
        self::assertInstanceOf(
            User::class,
            $user,
            'The seed is expected to contain a user for the member.',
        );

        self::getContainer()->get('security.token_storage')->setToken(
            new UsernamePasswordToken(
                $user,
                'main',
                $roles,
            ),
        );
    }
}
