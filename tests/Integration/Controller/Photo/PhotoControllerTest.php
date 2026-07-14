<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Photo;

use App\Controller\Photo\PhotoController;
use App\Entity\Application\Enums\StorageNamespace;
use App\Entity\Photo\Album;
use App\Entity\Photo\Enums\AlbumType;
use App\Entity\Photo\Photo;
use App\Entity\User\Enums\UserRoles;
use App\Entity\User\User;
use App\Repository\Photo\AlbumRepository;
use App\Service\Application\FileStorage;
use App\Tests\Integration\DatabaseTestCase;
use DateTime;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

use function imagecreatetruecolor;
use function imagejpeg;
use function json_decode;
use function str_contains;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

use const JSON_THROW_ON_ERROR;

/**
 * The photo browsing controller, invoked directly (the codebase has no WebTestCase). Covers the album view and manifest
 * access rules, the signed URLs in the manifest, and the download authorization and filename.
 */
final class PhotoControllerTest extends DatabaseTestCase
{
    public function testAlbumPageRendersForAViewableAlbum(): void
    {
        $this->authenticate(
            8030,
            UserRoles::Member,
        );
        $this->pushRequest();

        $response = $this->controller()->album(
            AlbumType::Regular,
            $this->albumId('Trip 2024'),
        );

        self::assertSame(
            Response::HTTP_OK,
            $response->getStatusCode(),
        );
    }

    public function testIndexRendersTheYearGroupedAlbumOverview(): void
    {
        $this->authenticate(
            8030,
            UserRoles::Member,
        );
        $this->pushRequest();

        $response = $this->controller()->index();

        self::assertSame(
            Response::HTTP_OK,
            $response->getStatusCode(),
        );
        // The seed's Gala and Trip albums share an association year, so the overview renders at least one month group.
        self::assertStringContainsString(
            'month-divider',
            (string) $response->getContent(),
        );
    }

    public function testAlbumPageIsNotFoundForAnUnpublishedAlbum(): void
    {
        $this->authenticate(
            8030,
            UserRoles::Member,
        );

        $this->expectException(NotFoundHttpException::class);
        $this->controller()->album(
            AlbumType::Regular,
            $this->draftAlbumId(),
        );
    }

    public function testAlbumPageIsNotFoundForAnUnbuiltVirtualAlbumType(): void
    {
        $this->authenticate(
            8030,
            UserRoles::Member,
        );

        // The weekly and body virtual albums are not browsable yet (member albums are — see below).
        $this->expectException(NotFoundHttpException::class);
        $this->controller()->album(
            AlbumType::Weekly,
            $this->albumId('Trip 2024'),
        );
    }

    public function testMemberAlbumRendersTheTaggedPhotos(): void
    {
        $this->authenticate(
            8030,
            UserRoles::Member,
        );
        $this->pushRequest();

        // Member 8030 is tagged in several seeded photos, so their member album is non-empty.
        $response = $this->controller()->album(
            AlbumType::Member,
            8030,
        );

        self::assertSame(
            Response::HTTP_OK,
            $response->getStatusCode(),
        );
    }

    public function testMemberAlbumIsNotFoundForAnUnknownMember(): void
    {
        $this->authenticate(
            8030,
            UserRoles::Member,
        );

        $this->expectException(NotFoundHttpException::class);
        $this->controller()->album(
            AlbumType::Member,
            99999999,
        );
    }

    public function testManifestReturnsSignedEntriesForEachPhoto(): void
    {
        $this->authenticate(
            8030,
            UserRoles::Member,
        );

        $response = $this->controller()->manifest($this->albumId('Trip 2024'));

        $entries = json_decode(
            (string) $response->getContent(),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
        self::assertIsArray($entries);
        self::assertCount(
            2,
            $entries,
            'The trip album is seeded with two photos.',
        );

        $first = $entries[0];
        self::assertIsArray($first);
        self::assertArrayHasKey(
            'id',
            $first,
        );
        self::assertArrayHasKey(
            'thumbUrl',
            $first,
        );
        self::assertArrayHasKey(
            'largeUrl',
            $first,
        );
        self::assertArrayHasKey(
            'xlargeUrl',
            $first,
        );
        self::assertArrayHasKey(
            'downloadUrl',
            $first,
        );
        // Album photos are a private namespace, so the variant URLs must be day-signed.
        self::assertTrue(str_contains((string) $first['largeUrl'], 'signature='));
        self::assertStringContainsString(
            '/download',
            (string) $first['downloadUrl'],
        );
    }

    public function testManifestIsNotFoundForAnUnpublishedAlbum(): void
    {
        $this->authenticate(
            8030,
            UserRoles::Member,
        );

        $this->expectException(NotFoundHttpException::class);
        $this->controller()->manifest($this->draftAlbumId());
    }

    public function testDownloadServesTheOriginalWithACompositeFilename(): void
    {
        $this->authenticate(
            8030,
            UserRoles::Member,
        );
        $photo = $this->storedTripPhoto();
        $photoId = (int) $photo->getId();

        $response = $this->controller()->download(
            $photo->getAlbum()->getId() ?? 0,
            $photoId,
        );

        // The filename is "{album slug}-{album year}-{photo id}.{ext}"; the seeded year is relative, so only assert the
        // stable parts around it.
        $disposition = (string) $response->headers->get('Content-Disposition');
        self::assertStringContainsString(
            'attachment',
            $disposition,
        );
        self::assertStringContainsString(
            'trip-2024-',
            $disposition,
        );
        self::assertStringContainsString(
            '-' . $photoId . '.jpg',
            $disposition,
        );
    }

    public function testDownloadIsNotFoundWhenThePhotoIsNotInTheAlbum(): void
    {
        $this->authenticate(
            8030,
            UserRoles::Member,
        );
        $photo = $this->storedTripPhoto();
        $otherAlbum = $this->draftAlbumId();

        $this->expectException(NotFoundHttpException::class);
        $this->controller()->download(
            $otherAlbum,
            (int) $photo->getId(),
        );
    }

    private function controller(): PhotoController
    {
        return self::getContainer()->get(PhotoController::class);
    }

    private function albumId(string $name): int
    {
        $album = self::getContainer()->get(AlbumRepository::class)->findOneBy(['name' => $name]);
        self::assertInstanceOf(
            Album::class,
            $album,
            'The seed is expected to contain the album.',
        );

        return (int) $album->getId();
    }

    private function draftAlbumId(): int
    {
        $album = self::getContainer()->get(AlbumRepository::class)->findOneBy(['published' => false]);
        self::assertInstanceOf(
            Album::class,
            $album,
            'The seed is expected to contain an unpublished album.',
        );

        return (int) $album->getId();
    }

    /**
     * A photo in the Trip album backed by a freshly stored image, so its original is present in the in-memory test
     * storage. The seed's own photos were stored in a different process, so their bytes are not available here.
     */
    private function storedTripPhoto(): Photo
    {
        $album = self::getContainer()->get(AlbumRepository::class)->findOneBy(['name' => 'Trip 2024']);
        self::assertInstanceOf(
            Album::class,
            $album,
            'The seed is expected to contain the Trip album.',
        );

        $temporaryFile = tempnam(
            sys_get_temp_dir(),
            'gewisweb-photo-test',
        );
        self::assertIsString($temporaryFile);
        $image = imagecreatetruecolor(
            16,
            16,
        );
        self::assertNotFalse($image);
        imagejpeg(
            $image,
            $temporaryFile,
        );

        $stored = self::getContainer()->get(FileStorage::class)->store(
            StorageNamespace::PhotoOriginal,
            $temporaryFile,
            (string) $album->getId(),
        );
        unlink($temporaryFile);

        $photo = new Photo();
        $photo->setAlbum($album);
        $photo->setPath($stored->path);
        $photo->setDateTime(new DateTime());
        $photo->setAspectRatio(1.0);
        $this->entityManager->persist($photo);
        $this->entityManager->flush();

        return $photo;
    }

    private function pushRequest(): void
    {
        $session = self::getContainer()->get('session.factory')->createSession();
        self::assertInstanceOf(
            FlashBagAwareSessionInterface::class,
            $session,
        );

        $request = new Request();
        $request->setSession($session);
        self::getContainer()->get('request_stack')->push($request);
    }

    private function authenticate(
        int $lidnr,
        UserRoles $role,
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
                [$role->value],
            ),
        );
    }
}
