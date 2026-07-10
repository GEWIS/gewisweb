<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Photo;

use App\Controller\Photo\AdminController;
use App\Entity\Application\Enums\StorageNamespace;
use App\Entity\Photo\Album;
use App\Entity\Photo\Photo;
use App\Entity\User\Enums\UserRoles;
use App\Entity\User\User;
use App\Repository\Photo\AlbumRepository;
use App\Repository\Photo\PhotoRepository;
use App\Service\Application\FileStorage;
use App\Tests\Integration\DatabaseTestCase;
use DateTime;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

use function imagecolorallocate;
use function imagecreatetruecolor;
use function imagefilledrectangle;
use function imagejpeg;
use function json_decode;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

use const JSON_THROW_ON_ERROR;

/**
 * The photo admin controller, invoked directly (the codebase has no WebTestCase). It covers the year-grouped overview,
 * the JSON upload endpoint, and the bulk move and delete of a photo selection. The class-level board guard and the CSRF
 * attributes are enforced by the framework at the HTTP layer, so a direct call exercises the action body itself.
 */
final class PhotoAdminControllerTest extends DatabaseTestCase
{
    public function testIndexRendersTheYearGroupedOverview(): void
    {
        $this->authenticateBoard();
        $this->pushRequest();

        $response = $this->controller()->index();

        self::assertSame(
            Response::HTTP_OK,
            $response->getStatusCode(),
        );
        self::assertStringContainsString(
            'Trip 2024',
            (string) $response->getContent(),
        );
    }

    public function testUploadStoresPhotosAndReturnsASummary(): void
    {
        $this->authenticateBoard();
        $album = $this->album('Trip 2024');

        $request = new Request();
        $request->files->set(
            'photos',
            [$this->imageUpload()],
        );

        $response = $this->controller()->upload(
            $album,
            $request,
        );

        $summary = json_decode(
            (string) $response->getContent(),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
        self::assertSame(
            [
                'created' => 1,
                'duplicates' => 0,
                'failed' => 0,
            ],
            $summary,
        );
    }

    public function testMovePhotosReassignsTheSelection(): void
    {
        $this->authenticateBoard();
        $this->pushRequest();
        $trip = $this->album('Trip 2024');
        $gala = $this->album('Gala 2024');
        $photo = $this->storedPhoto($trip);

        $request = new Request();
        $request->request->set(
            'photos',
            [(string) $photo->getId()],
        );
        $request->request->set(
            'destination',
            (string) $gala->getId(),
        );

        $response = $this->controller()->movePhotos(
            $trip,
            $request,
        );

        self::assertInstanceOf(
            RedirectResponse::class,
            $response,
        );
        self::assertSame(
            $gala->getId(),
            $photo->getAlbum()->getId(),
        );
    }

    public function testDeletePhotosRemovesTheSelection(): void
    {
        $this->authenticateBoard();
        $this->pushRequest();
        $trip = $this->album('Trip 2024');
        $photo = $this->storedPhoto($trip);
        $id = (int) $photo->getId();

        $request = new Request();
        $request->request->set(
            'photos',
            [(string) $id],
        );

        $response = $this->controller()->deletePhotos(
            $trip,
            $request,
        );

        self::assertInstanceOf(
            RedirectResponse::class,
            $response,
        );
        self::assertNull($this->photoRepository()->find($id));
    }

    private function controller(): AdminController
    {
        return self::getContainer()->get(AdminController::class);
    }

    private function photoRepository(): PhotoRepository
    {
        return self::getContainer()->get(PhotoRepository::class);
    }

    private function album(string $name): Album
    {
        $album = self::getContainer()->get(AlbumRepository::class)->findOneBy(['name' => $name]);
        self::assertInstanceOf(
            Album::class,
            $album,
            'The seed is expected to contain the album.',
        );

        return $album;
    }

    private function imageUpload(): UploadedFile
    {
        $path = tempnam(
            sys_get_temp_dir(),
            'gewisweb-admin-controller-test',
        );
        self::assertIsString($path);
        $image = imagecreatetruecolor(
            44,
            28,
        );
        self::assertNotFalse($image);
        $colour = imagecolorallocate(
            $image,
            0x0A,
            0x5B,
            0x2C,
        );
        self::assertNotFalse($colour);
        imagefilledrectangle(
            $image,
            0,
            0,
            44,
            28,
            $colour,
        );
        imagejpeg(
            $image,
            $path,
        );

        return new UploadedFile(
            $path,
            'photo.jpg',
            'image/jpeg',
            null,
            true,
        );
    }

    private function storedPhoto(Album $album): Photo
    {
        $file = tempnam(
            sys_get_temp_dir(),
            'gewisweb-admin-controller-test',
        );
        self::assertIsString($file);
        $image = imagecreatetruecolor(
            36,
            36,
        );
        self::assertNotFalse($image);
        $colour = imagecolorallocate(
            $image,
            0x7C,
            0x1D,
            0x4F,
        );
        self::assertNotFalse($colour);
        imagefilledrectangle(
            $image,
            0,
            0,
            36,
            36,
            $colour,
        );
        imagejpeg(
            $image,
            $file,
        );

        $stored = self::getContainer()->get(FileStorage::class)->store(
            StorageNamespace::PhotoOriginal,
            $file,
            (string) $album->getId(),
        );
        unlink($file);

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

    private function authenticateBoard(): void
    {
        $user = $this->entityManager->getRepository(User::class)->find(8025);
        self::assertInstanceOf(
            User::class,
            $user,
            'The seed is expected to contain a board member.',
        );

        self::getContainer()->get('security.token_storage')->setToken(
            new UsernamePasswordToken(
                $user,
                'main',
                [UserRoles::Board->value],
            ),
        );
    }
}
