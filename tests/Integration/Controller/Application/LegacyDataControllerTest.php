<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Application;

use App\Controller\Application\LegacyDataController;
use App\Entity\Decision\Organ;
use App\Entity\Decision\OrganInformation;
use App\Entity\Photo\Album;
use App\Entity\Photo\Photo;
use App\Entity\User\Enums\UserRoles;
use App\Entity\User\User;
use App\Repository\Photo\AlbumRepository;
use App\Tests\Integration\DatabaseTestCase;
use DateTime;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * The legacy `/data/{path}` redirect controller. Company paths map deterministically; flat paths are disambiguated by
 * filename against the seeded MariaDB. Photo originals never 301 to an image URL: an authorised member is redirected to
 * the viewer and everyone else is denied. Invoked directly, since the codebase has no WebTestCase.
 */
final class LegacyDataControllerTest extends DatabaseTestCase
{
    public function testCompanyPathRedirectsToTheCareerImageUrl(): void
    {
        $response = $this->controller()->resolve('company/5/ab/deadbeef.png');

        self::assertSame(
            Response::HTTP_MOVED_PERMANENTLY,
            $response->getStatusCode(),
        );
        self::assertStringContainsString(
            '/img/w960/career/5/images/deadbeef.png',
            (string) $response->headers->get('Location'),
        );
    }

    public function testAlbumCoverRedirectsToTheCoverVariant(): void
    {
        $album = $this->publishedAlbum();
        $album->setCoverPath('photos/covers/' . ($album->getId() ?? 0) . '/legacy-cover-c12.jpg');
        $this->entityManager->flush();

        $response = $this->controller()->resolve('cd/legacy-cover-c12.jpg');

        self::assertSame(
            Response::HTTP_MOVED_PERMANENTLY,
            $response->getStatusCode(),
        );
        self::assertStringContainsString(
            '/img/cover/photos/covers/',
            (string) $response->headers->get('Location'),
        );
    }

    public function testOrganImageRedirectsToTheServedUrl(): void
    {
        $organ = $this->entityManager->getRepository(Organ::class)->findOneBy([]);
        self::assertInstanceOf(
            Organ::class,
            $organ,
        );
        $information = new OrganInformation();
        $information->setOrgan($organ);
        $information->setCoverPath('organs/images/legacy-organ-c12.jpg');
        $this->entityManager->persist($information);
        $this->entityManager->flush();

        $response = $this->controller()->resolve('ef/legacy-organ-c12.jpg');

        self::assertSame(
            Response::HTTP_MOVED_PERMANENTLY,
            $response->getStatusCode(),
        );
        self::assertStringContainsString(
            '/img/w960/organs/images/legacy-organ-c12.jpg',
            (string) $response->headers->get('Location'),
        );
    }

    public function testPhotoOriginalRedirectsAMemberToTheViewer(): void
    {
        $this->authenticateMember();
        $photo = $this->migratedPhoto('legacy-photo-c12.jpg');

        $response = $this->controller()->resolve('12/legacy-photo-c12.jpg');

        self::assertSame(
            Response::HTTP_FOUND,
            $response->getStatusCode(),
        );
        self::assertStringContainsString(
            '#pid=' . ($photo->getId() ?? 0),
            (string) $response->headers->get('Location'),
        );
    }

    public function testPhotoOriginalIsDeniedToAnonymous(): void
    {
        $this->migratedPhoto('legacy-photo-anon-c12.jpg');

        $this->expectException(AccessDeniedHttpException::class);
        $this->controller()->resolve('34/legacy-photo-anon-c12.jpg');
    }

    public function testUnknownPathIsNotFound(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->controller()->resolve('zz/does-not-exist-c12.jpg');
    }

    private function controller(): LegacyDataController
    {
        return self::getContainer()->get(LegacyDataController::class);
    }

    private function publishedAlbum(): Album
    {
        $album = self::getContainer()->get(AlbumRepository::class)->findOneBy(['name' => 'Trip 2024']);
        self::assertInstanceOf(
            Album::class,
            $album,
            'The seed is expected to contain the Trip album.',
        );

        return $album;
    }

    /**
     * A photo in a published album whose stored path re-roots the given legacy filename, as the migration would leave
     * it. The file itself is not needed (the controller only reads the path).
     */
    private function migratedPhoto(string $basename): Photo
    {
        $album = $this->publishedAlbum();

        $photo = new Photo();
        $photo->setAlbum($album);
        $photo->setPath('photos/albums/' . ($album->getId() ?? 0) . '/' . $basename);
        $photo->setDateTime(new DateTime());
        $photo->setAspectRatio(1.0);
        $this->entityManager->persist($photo);
        $this->entityManager->flush();

        return $photo;
    }

    private function authenticateMember(): void
    {
        $user = $this->entityManager->getRepository(User::class)->find(8030);
        self::assertInstanceOf(
            User::class,
            $user,
            'The seed is expected to contain a user for the member.',
        );

        self::getContainer()->get('security.token_storage')->setToken(
            new UsernamePasswordToken(
                $user,
                'main',
                [UserRoles::Member->value],
            ),
        );
    }
}
