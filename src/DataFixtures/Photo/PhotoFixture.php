<?php

declare(strict_types=1);

namespace App\DataFixtures\Photo;

use App\DataFixtures\Decision\DecisionFixture;
use App\DataFixtures\Decision\MemberFixture;
use App\Entity\Application\Enums\StorageNamespace;
use App\Entity\Decision\Member;
use App\Entity\Decision\Organ;
use App\Entity\Photo\Album;
use App\Entity\Photo\MemberTag;
use App\Entity\Photo\OrganTag;
use App\Entity\Photo\Photo;
use App\Entity\Photo\ProfilePhoto;
use App\Entity\Photo\Vote;
use App\Entity\Photo\WeeklyPhoto;
use App\Service\Application\FileStorage;
use DateTime;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use GdImage;
use Override;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

use function abs;
use function count;
use function crc32;
use function imagecolorallocate;
use function imagecreatetruecolor;
use function imagefilledrectangle;
use function imagejpeg;
use function imagestring;
use function intval;
use function min;
use function sprintf;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

/**
 * Seeds a photo tree so the authorization matrix (published/unpublished, sub-albums, member and organ tags, the #1658
 * graduate-in-subtree rule), voting, the weekly photo and profile photos can all be exercised by integration tests and
 * browsed in dev.
 *
 * The core tree below is always seeded and is what the integration tests assert against:
 *   Gala 2024                (published)
 *     ├─ Gala 2024 – Dinner       (published)   a graduate is tagged here
 *     └─ Gala 2024 – Afterparty   (published)   a different graduate is tagged here
 *   Trip 2024                (published)         has the visible weekly photo
 *   Draft Album              (unpublished)
 *
 * Outside the test environment a larger set of demo albums is added on top (see {@see loadDemoAlbums}) so the year
 * filter, month dividers, masonry grid and viewer paging can be browsed with realistic data.
 *
 * Images are generated on the fly rather than committed to the repository, so an album can hold two hundred photos
 * without shipping any binaries. The three graduate lidnrs below drive the #1658 regression suite:
 * {@see GRADUATE_TAGGED_IN_SUBTREE} is tagged in a sub-album and must therefore be able to view the parent Gala album;
 * {@see GRADUATE_TAGGED_IN_OTHER_SUBALBUM} is tagged in the sibling sub-album (so may view Gala, but not the Dinner
 * sub-album on its own); and {@see GRADUATE_TAGGED_NOWHERE} is tagged nowhere and may view nothing.
 */
class PhotoFixture extends Fixture implements DependentFixtureInterface
{
    public const string REFERENCE_ALBUM_GALA = 'photo-album-gala';
    public const string REFERENCE_ALBUM_GALA_DINNER = 'photo-album-gala-dinner';
    public const string REFERENCE_ALBUM_GALA_AFTERPARTY = 'photo-album-gala-afterparty';
    public const string REFERENCE_ALBUM_TRIP = 'photo-album-trip';
    public const string REFERENCE_ALBUM_SECRET = 'photo-album-secret';
    public const string REFERENCE_PHOTO_DINNER = 'photo-dinner-1';
    public const string REFERENCE_PHOTO_AFTERPARTY = 'photo-afterparty-1';
    public const string REFERENCE_PHOTO_TRIP = 'photo-trip-1';
    public const string REFERENCE_PHOTO_SECRET = 'photo-secret-1';

    /** A graduate tagged in the Dinner sub-album, so must be able to view the parent Gala album (#1658). */
    public const int GRADUATE_TAGGED_IN_SUBTREE = 8155;

    /** A graduate tagged in the sibling Afterparty sub-album (inside the Gala tree, but not in Dinner's). */
    public const int GRADUATE_TAGGED_IN_OTHER_SUBALBUM = 8156;

    /** A graduate tagged nowhere, so may view no members-only album. */
    public const int GRADUATE_TAGGED_NOWHERE = 8157;

    /** Landscape, portrait and square shapes, so the masonry grid and viewer see a realistic mix of aspect ratios. */
    private const array SHAPES = [
        [
            1200,
            800,
        ],
        [
            800,
            1200,
        ],
        [
            1000,
            1000,
        ],
        [
            1280,
            720,
        ],
        [
            900,
            1200,
        ],
        [
            1200,
            900,
        ],
    ];

    /** Distinct background colours so generated thumbnails are told apart at a glance. */
    private const array PALETTE = [
        [
            198,
            40,
            40,
        ],
        [
            21,
            101,
            192,
        ],
        [
            46,
            125,
            50,
        ],
        [
            245,
            124,
            0,
        ],
        [
            106,
            27,
            154,
        ],
        [
            0,
            131,
            143,
        ],
        [
            55,
            71,
            79,
        ],
        [
            191,
            54,
            12,
        ],
    ];

    private int $photoCounter = 0;

    public function __construct(
        private readonly FileStorage $fileStorage,
        #[Autowire('%kernel.environment%')]
        private readonly string $environment,
    ) {
    }

    #[Override]
    public function load(ObjectManager $manager): void
    {
        $gala = $this->makeAlbum(
            'Gala 2024',
            true,
            null,
            '-6 months',
        );
        $manager->persist($gala);
        $this->addReference(
            self::REFERENCE_ALBUM_GALA,
            $gala,
        );

        $dinner = $this->makeAlbum(
            'Gala 2024 – Dinner',
            true,
            $gala,
            '-6 months',
        );
        $manager->persist($dinner);
        $this->addReference(
            self::REFERENCE_ALBUM_GALA_DINNER,
            $dinner,
        );

        $afterparty = $this->makeAlbum(
            'Gala 2024 – Afterparty',
            true,
            $gala,
            '-6 months 4 hours',
        );
        $manager->persist($afterparty);
        $this->addReference(
            self::REFERENCE_ALBUM_GALA_AFTERPARTY,
            $afterparty,
        );

        $trip = $this->makeAlbum(
            'Trip 2024',
            true,
            null,
            '-3 months',
        );
        $manager->persist($trip);
        $this->addReference(
            self::REFERENCE_ALBUM_TRIP,
            $trip,
        );

        $secret = $this->makeAlbum(
            'Draft Album',
            false,
            null,
            '-1 week',
        );
        $manager->persist($secret);
        $this->addReference(
            self::REFERENCE_ALBUM_SECRET,
            $secret,
        );

        // Flush the albums first so their ids exist: photo originals are stored scoped per album.
        $manager->flush();

        // --- Dinner sub-album ---
        $dinnerPhoto = $this->makePhoto(
            $dinner,
            '-6 months 20:15',
        );
        $manager->persist($dinnerPhoto);
        $this->addReference(
            self::REFERENCE_PHOTO_DINNER,
            $dinnerPhoto,
        );
        // A graduate pinned to a point in the image, plus a whole-photo tag and an organ tag.
        $manager->persist($this->memberTag($dinnerPhoto, self::GRADUATE_TAGGED_IN_SUBTREE, 0.52, 0.41));
        $manager->persist($this->memberTag($dinnerPhoto, 8030, null, null));
        $manager->persist($this->organTag($dinnerPhoto, 'organ-getest'));

        $dinnerPhoto2 = $this->makePhoto(
            $dinner,
            '-6 months 20:45',
        );
        $manager->persist($dinnerPhoto2);
        $manager->persist($this->memberTag($dinnerPhoto2, 8031, null, null));

        // --- Afterparty sub-album ---
        $afterPhoto = $this->makePhoto(
            $afterparty,
            '-6 months 23:50',
        );
        $manager->persist($afterPhoto);
        $this->addReference(
            self::REFERENCE_PHOTO_AFTERPARTY,
            $afterPhoto,
        );
        $manager->persist($this->memberTag($afterPhoto, self::GRADUATE_TAGGED_IN_OTHER_SUBALBUM, null, null));

        // --- Trip album (public weekly photo lives here) ---
        $tripPhoto = $this->makePhoto(
            $trip,
            '-3 months 14:00',
        );
        $manager->persist($tripPhoto);
        $this->addReference(
            self::REFERENCE_PHOTO_TRIP,
            $tripPhoto,
        );
        $manager->persist($this->memberTag($tripPhoto, 8030, 0.3, 0.6));
        $manager->persist($this->organTag($tripPhoto, 'organ-keur'));
        $manager->persist(new Vote($tripPhoto, $this->member(8025)));
        $manager->persist(new Vote($tripPhoto, $this->member(8026)));
        $manager->persist(new Vote($tripPhoto, $this->member(8027)));

        $tripPhoto2 = $this->makePhoto(
            $trip,
            '-3 months 15:30',
        );
        $manager->persist($tripPhoto2);
        $manager->persist(new Vote($tripPhoto2, $this->member(8028)));

        // --- Draft (unpublished) album ---
        $secretPhoto = $this->makePhoto(
            $secret,
            '-1 week 12:00',
        );
        $manager->persist($secretPhoto);
        $this->addReference(
            self::REFERENCE_PHOTO_SECRET,
            $secretPhoto,
        );
        $manager->persist($this->memberTag($secretPhoto, 8030, null, null));

        // The visible weekly photo (from the trip) and an older hidden one (from the dinner), so the anonymous
        // frontpage visibility rule has both cases to exercise.
        $manager->persist($this->weeklyPhoto($tripPhoto, '-1 week', false));
        $manager->persist($this->weeklyPhoto($dinnerPhoto, '-5 months', true));

        // A profile photo for member 8030, taken from a photo they are tagged in.
        $profilePhoto = new ProfilePhoto();
        $profilePhoto->setPhoto($dinnerPhoto);
        $profilePhoto->setMember($this->member(8030));
        $profilePhoto->setDateTime(new DateTime());
        $profilePhoto->setExplicit(false);
        $manager->persist($profilePhoto);

        if ('test' !== $this->environment) {
            $this->loadDemoAlbums($manager);
        }

        $manager->flush();
    }

    /**
     * Adds a broad spread of published albums across the last three association years and many months, with photo
     * counts from a handful up to two hundred, so every part of the browsing UI has realistic data to render.
     */
    private function loadDemoAlbums(ObjectManager $manager): void
    {
        // [name, days ago, number of photos]. The day offsets are chosen to fall in distinct months across three
        // association years, so both the year filter and the month dividers have something to separate.
        $albums = [
            [
                'Introduction Camp',
                12,
                200,
            ],
            [
                'General Members Meeting',
                26,
                18,
            ],
            [
                'Symposium',
                54,
                40,
            ],
            [
                'Christmas Drinks',
                96,
                75,
            ],
            [
                'New Year\'s Dinner',
                128,
                30,
            ],
            [
                'Skitrip',
                165,
                120,
            ],
            [
                'Study Trip Lisbon',
                210,
                90,
            ],
            [
                'Spring Barbecue',
                250,
                24,
            ],
            [
                'Alumni Reunion',
                400,
                60,
            ],
            [
                'Anniversary Gala',
                445,
                150,
            ],
            [
                'Autumn Hike',
                505,
                12,
            ],
            [
                'Freshmen Weekend',
                760,
                45,
            ],
            [
                'Board Handover',
                815,
                8,
            ],
        ];

        foreach ($albums as [$name, $daysAgo, $count]) {
            $album = $this->makeAlbum(
                $name,
                true,
                null,
                sprintf(
                    '-%d days',
                    $daysAgo,
                ),
            );
            $manager->persist($album);
            $manager->flush();

            for ($i = 0; $i < $count; ++$i) {
                $manager->persist($this->makePhoto(
                    $album,
                    sprintf(
                        '-%d days +%d minutes',
                        $daysAgo,
                        $i * 3,
                    ),
                ));
            }
        }

        // One album that itself contains sub-albums, to exercise the sub-album count on the cards and the nested view.
        $festival = $this->makeAlbum(
            'Summer Festival',
            true,
            null,
            '-70 days',
        );
        $manager->persist($festival);
        $manager->flush();

        foreach (['Main Stage', 'Camping', 'After Movie'] as $day => $stage) {
            $subAlbum = $this->makeAlbum(
                'Summer Festival – ' . $stage,
                true,
                $festival,
                sprintf(
                    '-70 days +%d hours',
                    $day,
                ),
            );
            $manager->persist($subAlbum);
            $manager->flush();

            for ($i = 0; $i < 20; ++$i) {
                $manager->persist($this->makePhoto(
                    $subAlbum,
                    sprintf(
                        '-70 days +%d hours +%d minutes',
                        $day,
                        $i * 2,
                    ),
                ));
            }
        }
    }

    private function makeAlbum(
        string $name,
        bool $published,
        ?Album $parent,
        string $startDateTime,
    ): Album {
        $album = new Album();
        $album->setName($name);
        $album->setPublished($published);
        $album->setStartDateTime(new DateTime($startDateTime));
        $album->setEndDateTime(new DateTime($startDateTime));

        if (null !== $parent) {
            $album->setParent($parent);
        }

        return $album;
    }

    private function makePhoto(
        Album $album,
        string $dateTime,
    ): Photo {
        ++$this->photoCounter;
        [
            $width, $height
        ] = self::SHAPES[abs($this->photoCounter) % count(self::SHAPES)];

        $temporaryFile = $this->generateImage(
            $width,
            $height,
            'Photo ' . $this->photoCounter,
        );

        try {
            $stored = $this->fileStorage->store(
                StorageNamespace::PhotoOriginal,
                $temporaryFile,
                (string) $album->getId(),
            );
        } finally {
            unlink($temporaryFile);
        }

        $photo = new Photo();
        $photo->setAlbum($album);
        $photo->setPath($stored->path);
        $photo->setDateTime(new DateTime($dateTime));
        // Aspect ratio is height / width, matching the pre-migration convention.
        $photo->setAspectRatio($height / $width);

        return $photo;
    }

    /**
     * Draws a coloured placeholder image (a background band with a caption) to a temporary file and returns its path.
     * The caption makes every photo's bytes unique, so content-addressed storage does not collapse them into one file.
     */
    private function generateImage(
        int $width,
        int $height,
        string $caption,
    ): string {
        $image = imagecreatetruecolor(
            $width,
            $height,
        );
        if (false === $image) {
            throw new RuntimeException('Cannot create a canvas for a fixture image.');
        }

        [
            $red,
            $green, $blue
        ] = self::PALETTE[abs(crc32($caption)) % count(self::PALETTE)];
        $background = $this->allocateColor(
            $image,
            $red,
            $green,
            $blue,
        );
        imagefilledrectangle(
            $image,
            0,
            0,
            $width,
            $height,
            $background,
        );

        $band = $this->allocateColor(
            $image,
            min(
                255,
                $red + 45,
            ),
            min(
                255,
                $green + 45,
            ),
            min(
                255,
                $blue + 45,
            ),
        );
        imagefilledrectangle(
            $image,
            0,
            intval((float) $height * 0.72),
            $width,
            $height,
            $band,
        );

        $white = $this->allocateColor(
            $image,
            255,
            255,
            255,
        );
        imagestring(
            $image,
            5,
            intval($width / 2) - 30,
            intval($height / 2) - 8,
            $caption,
            $white,
        );

        $temporaryFile = tempnam(
            sys_get_temp_dir(),
            'gewisweb-fixture-photo',
        );
        if (false === $temporaryFile) {
            throw new RuntimeException('Cannot create a temporary file for a fixture image.');
        }

        imagejpeg(
            $image,
            $temporaryFile,
            82,
        );

        return $temporaryFile;
    }

    private function allocateColor(
        GdImage $image,
        int $red,
        int $green,
        int $blue,
    ): int {
        $color = imagecolorallocate(
            $image,
            $red,
            $green,
            $blue,
        );
        if (false === $color) {
            throw new RuntimeException('Cannot allocate a colour for a fixture image.');
        }

        return $color;
    }

    private function memberTag(
        Photo $photo,
        int $lidnr,
        ?float $x,
        ?float $y,
    ): MemberTag {
        $tag = new MemberTag();
        $tag->setPhoto($photo);
        $tag->setMember($this->member($lidnr));
        $tag->setPosition(
            $x,
            $y,
        );

        return $tag;
    }

    private function organTag(
        Photo $photo,
        string $organReference,
    ): OrganTag {
        $tag = new OrganTag();
        $tag->setPhoto($photo);
        $tag->setOrgan($this->getReference($organReference, Organ::class));

        return $tag;
    }

    private function weeklyPhoto(
        Photo $photo,
        string $week,
        bool $hidden,
    ): WeeklyPhoto {
        $weeklyPhoto = new WeeklyPhoto();
        $weeklyPhoto->setPhoto($photo);
        $weeklyPhoto->setWeek(new DateTime($week));
        $weeklyPhoto->setHidden($hidden);

        return $weeklyPhoto;
    }

    private function member(int $lidnr): Member
    {
        return $this->getReference(
            'member-' . $lidnr,
            Member::class,
        );
    }

    /**
     * @return array<array-key, class-string<Fixture>>
     */
    #[Override]
    public function getDependencies(): array
    {
        return [
            MemberFixture::class,
            DecisionFixture::class,
        ];
    }
}
