<?php

declare(strict_types=1);

namespace App\Tests\Service\Photo;

use App\Entity\Application\Enums\StorageNamespace;
use App\Entity\Photo\Photo;
use App\Entity\Photo\Tag;
use App\Entity\Photo\WeeklyPhoto;
use App\Repository\Photo\PhotoRepository;
use App\Repository\Photo\VoteRepository;
use App\Repository\Photo\WeeklyPhotoRepository;
use App\Service\Application\FileStorage;
use App\Service\Photo\WeeklyPhotoService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\Filesystem;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

use function imagecolorallocate;
use function imagecreatetruecolor;
use function imagefilledrectangle;
use function imagejpeg;
use function in_array;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

/**
 * The weekly photo selection: the winner is the highest-rated photo voted on in the window, where rating up-weights
 * recency (a newer photo can beat one with more votes) and gives a tagged photo a 50% bonus, and a photo that has
 * already been photo of the week is never chosen again. The chosen original is copied into the public namespace so the
 * anonymous frontpage can serve it, and hiding removes that public copy. Verified on the GD driver with in-memory
 * storage and stubbed repositories, so the algorithm is exercised without a database.
 */
final class WeeklyPhotoServiceTest extends TestCase
{
    public function testPicksTheHigherRatedRecentPhotoAndPublishesIt(): void
    {
        $storage = $this->storage();
        $recent = $this->storedPhoto(
            $storage,
            1,
            '-1 day',
        );
        $old = $this->storedPhoto(
            $storage,
            2,
            '-100 days',
        );

        // Recency: 3 * (1 + 1/1) = 6 beats 5 * (1 + 1/100) = 5.05, so the newer photo wins despite fewer votes.
        $service = $this->service(
            $storage,
            [
                [
                    1 => 1,
                    2 => 3,
                ],
                [
                    1 => 2,
                    2 => 5,
                ],
            ],
            [
                1 => $recent,
                2 => $old,
            ],
            [],
        );

        $weeklyPhoto = $service->generatePhotoOfTheWeek();

        self::assertNotNull($weeklyPhoto);
        self::assertSame(
            $recent,
            $weeklyPhoto->getPhoto(),
        );
        self::assertTrue($storage->exists($service->publicPathFor($recent)));
    }

    public function testATaggedPhotoGetsTheFiftyPercentBonus(): void
    {
        $storage = $this->storage();
        $tagged = $this->storedPhoto(
            $storage,
            1,
            '-10 days',
            true,
        );
        $untagged = $this->storedPhoto(
            $storage,
            2,
            '-10 days',
        );

        // Same votes and age, so only the tag bonus (1.5x) separates them.
        $service = $this->service(
            $storage,
            [
                [
                    1 => 1,
                    2 => 4,
                ],
                [
                    1 => 2,
                    2 => 4,
                ],
            ],
            [
                1 => $tagged,
                2 => $untagged,
            ],
            [],
        );

        $weeklyPhoto = $service->generatePhotoOfTheWeek();

        self::assertNotNull($weeklyPhoto);
        self::assertSame(
            $tagged,
            $weeklyPhoto->getPhoto(),
        );
    }

    public function testNeverRepeatsAPreviousPhotoOfTheWeek(): void
    {
        $storage = $this->storage();
        $repeat = $this->storedPhoto(
            $storage,
            1,
            '-1 day',
        );
        $fresh = $this->storedPhoto(
            $storage,
            2,
            '-1 day',
        );

        // The repeat would rate highest, but it has been photo of the week before, so the fresh one is chosen.
        $service = $this->service(
            $storage,
            [
                [
                    1 => 1,
                    2 => 9,
                ],
                [
                    1 => 2,
                    2 => 2,
                ],
            ],
            [
                1 => $repeat,
                2 => $fresh,
            ],
            [1],
        );

        $weeklyPhoto = $service->generatePhotoOfTheWeek();

        self::assertNotNull($weeklyPhoto);
        self::assertSame(
            $fresh,
            $weeklyPhoto->getPhoto(),
        );
    }

    public function testReturnsNullWhenNothingWasVotedOn(): void
    {
        $storage = $this->storage();

        self::assertNull(
            $this->service(
                $storage,
                [],
                [],
                [],
            )->generatePhotoOfTheWeek(),
        );
    }

    public function testExpiresThePreviousPublicCopyEvenWhenNoPhotoIsChosen(): void
    {
        $storage = $this->storage();
        $previousPhoto = $this->storedPhoto(
            $storage,
            9,
            '-1 week',
        );
        $previous = new WeeklyPhoto();
        $previous->setPhoto($previousPhoto);
        $previous->setWeek(new DateTime('-1 week'));

        $service = $this->service(
            $storage,
            [],
            [],
            [],
            $previous,
        );
        $previousPublicPath = $service->publicPathFor($previousPhoto);
        $storage->write(
            $previousPublicPath,
            'stale-public-copy',
        );

        // No votes this week, so no new photo is chosen, but last week's public copy must still be dropped.
        self::assertNull($service->generatePhotoOfTheWeek());
        self::assertFalse($storage->exists($previousPublicPath));
    }

    public function testSetPhotoOfTheWeekForcesTheChosenPhotoAndPublishesIt(): void
    {
        $storage = $this->storage();
        $photo = $this->storedPhoto(
            $storage,
            1,
            '-1 day',
        );
        // An admin override skips the vote-based pick even though this photo has no votes at all.
        $service = $this->service(
            $storage,
            [],
            [],
            [],
        );

        $weeklyPhoto = $service->setPhotoOfTheWeek($photo);

        self::assertSame(
            $photo,
            $weeklyPhoto->getPhoto(),
        );
        self::assertTrue($storage->exists($service->publicPathFor($photo)));
    }

    public function testHideRemovesThePublicCopy(): void
    {
        $storage = $this->storage();
        $photo = $this->storedPhoto(
            $storage,
            1,
            '-1 day',
        );
        $service = $this->service(
            $storage,
            [],
            [],
            [],
        );

        $publicPath = $service->publicPathFor($photo);
        $storage->write(
            $publicPath,
            'public-copy-bytes',
        );

        $weeklyPhoto = new WeeklyPhoto();
        $weeklyPhoto->setPhoto($photo);
        $weeklyPhoto->setWeek(new DateTime());

        $service->hide($weeklyPhoto);

        self::assertTrue($weeklyPhoto->isHidden());
        self::assertFalse($storage->exists($publicPath));
    }

    private function storage(): FileStorage
    {
        return new FileStorage(new Filesystem(new InMemoryFilesystemAdapter()));
    }

    /**
     * @param array<array-key, array{1: int, 2: int}> $votes
     * @param array<int, Photo>                       $photosById
     * @param list<int>                               $repeats    ids that have already been photo of the week
     */
    private function service(
        FileStorage $storage,
        array $votes,
        array $photosById,
        array $repeats,
        ?WeeklyPhoto $current = null,
    ): WeeklyPhotoService {
        $voteRepository = self::createStub(VoteRepository::class);
        $voteRepository->method('getVotesInRange')->willReturn($votes);

        $photoRepository = self::createStub(PhotoRepository::class);
        $photoRepository->method('find')->willReturnCallback(
            static fn (mixed $id): ?Photo => $photosById[$id] ?? null,
        );

        $weeklyPhotoRepository = self::createStub(WeeklyPhotoRepository::class);
        $weeklyPhotoRepository->method('getCurrentPhotoOfTheWeek')->willReturn($current);
        $weeklyPhotoRepository->method('hasBeenPhotoOfTheWeek')->willReturnCallback(
            static fn (Photo $photo): bool => in_array(
                $photo->getId(),
                $repeats,
                true,
            ),
        );

        $messageBus = self::createStub(MessageBusInterface::class);
        $messageBus->method('dispatch')->willReturnCallback(
            static fn (object $message): Envelope => new Envelope($message),
        );

        return new WeeklyPhotoService(
            $photoRepository,
            $voteRepository,
            $weeklyPhotoRepository,
            $storage,
            self::createStub(EntityManagerInterface::class),
            $messageBus,
        );
    }

    private function storedPhoto(
        FileStorage $storage,
        int $id,
        string $ageModifier,
        bool $tagged = false,
    ): Photo {
        $file = tempnam(
            sys_get_temp_dir(),
            'gewisweb-weekly-test',
        );
        self::assertIsString($file);
        $image = imagecreatetruecolor(
            32,
            24,
        );
        self::assertNotFalse($image);
        $colour = imagecolorallocate(
            $image,
            $id * 20,
            0x40,
            0x80,
        );
        self::assertNotFalse($colour);
        imagefilledrectangle(
            $image,
            0,
            0,
            32,
            24,
            $colour,
        );
        imagejpeg(
            $image,
            $file,
        );

        $stored = $storage->store(
            StorageNamespace::PhotoOriginal,
            $file,
            (string) $id,
        );
        unlink($file);

        $photo = new Photo();
        new ReflectionProperty(
            Photo::class,
            'id',
        )->setValue(
            $photo,
            $id,
        );
        $photo->setPath($stored->path);
        $photo->setDateTime(new DateTime($ageModifier));
        if ($tagged) {
            $photo->addTag(self::createStub(Tag::class));
        }

        return $photo;
    }
}
