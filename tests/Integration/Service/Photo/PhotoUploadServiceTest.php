<?php

declare(strict_types=1);

namespace App\Tests\Integration\Service\Photo;

use App\Entity\Photo\Album;
use App\Message\Photo\GenerateAlbumCoverMessage;
use App\Message\Photo\ProcessImageVariantsMessage;
use App\Repository\Photo\AlbumRepository;
use App\Repository\Photo\PhotoRepository;
use App\Service\Photo\PhotoUploadService;
use App\Tests\Integration\DatabaseTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;

use function count;
use function file_put_contents;
use function imagecolorallocate;
use function imagecreatetruecolor;
use function imagefilledrectangle;
use function imagejpeg;
use function is_a;
use function sys_get_temp_dir;
use function tempnam;

/**
 * The upload service stores each file on its own so one bad file never aborts the batch: a real image becomes a photo
 * scoped to its album and queues variant + cover generation, a non-image is counted as failed without touching the
 * album, and bytes already present in the album are reported as duplicates. Storage is the in-memory adapter the test
 * environment binds, so the freshly stored bytes are readable here.
 */
final class PhotoUploadServiceTest extends DatabaseTestCase
{
    public function testStoresANewPhotoAndQueuesVariantAndCoverGeneration(): void
    {
        $album = $this->album('Trip 2024');
        $before = count($this->photoRepository()->getAlbumPhotos($album));

        $result = $this->service()->upload(
            $album,
            [
                $this->imageFile(
                    0x1E,
                    0x7A,
                    0x88,
                ),
            ],
        );

        self::assertSame(
            [
                'created' => 1,
                'duplicates' => 0,
                'failed' => 0,
            ],
            $result,
        );
        self::assertCount(
            $before + 1,
            $this->photoRepository()->getAlbumPhotos($album),
        );
        self::assertSame(
            1,
            $this->sentCount(ProcessImageVariantsMessage::class),
        );
        self::assertSame(
            1,
            $this->sentCount(GenerateAlbumCoverMessage::class),
        );
    }

    public function testRejectsANonImageFileWithoutTouchingTheAlbum(): void
    {
        $album = $this->album('Trip 2024');
        $before = count($this->photoRepository()->getAlbumPhotos($album));

        $result = $this->service()->upload(
            $album,
            [$this->textFile()],
        );

        self::assertSame(
            [
                'created' => 0,
                'duplicates' => 0,
                'failed' => 1,
            ],
            $result,
        );
        self::assertCount(
            $before,
            $this->photoRepository()->getAlbumPhotos($album),
        );
        // Nothing was created, so the album cover is left alone.
        self::assertSame(
            0,
            $this->sentCount(GenerateAlbumCoverMessage::class),
        );
    }

    public function testSkipsABytewiseDuplicateAlreadyInTheAlbum(): void
    {
        $album = $this->album('Trip 2024');
        // The same file twice in one batch: the first upload creates the photo, the second matches it and is a
        // duplicate (album-scoped dedup on the content hash).
        $path = $this->imagePath(
            0x33,
            0x66,
            0x99,
        );

        $result = $this->service()->upload(
            $album,
            [
                $this->uploadedFile($path),
                $this->uploadedFile($path),
            ],
        );

        self::assertSame(
            [
                'created' => 1,
                'duplicates' => 1,
                'failed' => 0,
            ],
            $result,
        );
    }

    private function service(): PhotoUploadService
    {
        return self::getContainer()->get(PhotoUploadService::class);
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

    /**
     * @param class-string $messageClass
     */
    private function sentCount(string $messageClass): int
    {
        $transport = self::getContainer()->get('messenger.transport.images');
        self::assertInstanceOf(
            InMemoryTransport::class,
            $transport,
        );

        $count = 0;
        foreach ($transport->getSent() as $envelope) {
            if (
                !is_a(
                    $envelope->getMessage(),
                    $messageClass,
                )
            ) {
                continue;
            }

            ++$count;
        }

        return $count;
    }

    private function imageFile(
        int $red,
        int $green,
        int $blue,
    ): UploadedFile {
        return $this->uploadedFile($this->imagePath($red, $green, $blue));
    }

    /**
     * A small solid-colour JPEG on disk; the colour keeps its content hash distinct from the seed's photos.
     */
    private function imagePath(
        int $red,
        int $green,
        int $blue,
    ): string {
        $path = tempnam(
            sys_get_temp_dir(),
            'gewisweb-upload-test',
        );
        self::assertIsString($path);

        $image = imagecreatetruecolor(
            48,
            32,
        );
        self::assertNotFalse($image);
        $colour = imagecolorallocate(
            $image,
            $red,
            $green,
            $blue,
        );
        self::assertNotFalse($colour);
        imagefilledrectangle(
            $image,
            0,
            0,
            48,
            32,
            $colour,
        );
        imagejpeg(
            $image,
            $path,
        );

        return $path;
    }

    private function textFile(): UploadedFile
    {
        $path = tempnam(
            sys_get_temp_dir(),
            'gewisweb-upload-test',
        );
        self::assertIsString($path);
        file_put_contents(
            $path,
            'this is not an image',
        );

        return new UploadedFile(
            $path,
            'notes.txt',
            'text/plain',
            null,
            true,
        );
    }

    private function uploadedFile(string $path): UploadedFile
    {
        return new UploadedFile(
            $path,
            'photo.jpg',
            'image/jpeg',
            null,
            true,
        );
    }
}
