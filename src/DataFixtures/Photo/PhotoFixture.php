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
use Override;
use RuntimeException;

use function getimagesize;
use function sprintf;

/**
 * Seeds a small but complete photo tree so the authorization matrix (published/unpublished, sub-albums, member and
 * organ tags, the #1658 graduate-in-subtree rule), voting, the weekly photo and profile photos can all be exercised by
 * integration tests and browsed in dev.
 *
 * The album shape is:
 *   Gala 2024                (published)
 *     ├─ Gala 2024 – Dinner       (published)   a graduate is tagged here
 *     └─ Gala 2024 – Afterparty   (published)   a different graduate is tagged here
 *   Trip 2024                (published)         has the visible weekly photo
 *   Draft Album              (unpublished)
 *
 * The three graduate lidnrs below drive the #1658 regression suite: {@see GRADUATE_TAGGED_IN_SUBTREE} is tagged in a
 * sub-album and must therefore be able to view the parent Gala album; {@see GRADUATE_TAGGED_IN_OTHER_SUBALBUM} is
 * tagged in the sibling sub-album (so may view Gala, but not the Dinner sub-album on its own); and
 * {@see GRADUATE_TAGGED_NOWHERE} is tagged nowhere and may view nothing.
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

    public function __construct(
        private readonly FileStorage $fileStorage,
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

        // --- Dinner sub-album ---
        $dinnerPhoto = $this->makePhoto(
            $dinner,
            'gala-dinner-1.jpg',
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
            'gala-dinner-2.jpg',
            '-6 months 20:45',
        );
        $manager->persist($dinnerPhoto2);
        $manager->persist($this->memberTag($dinnerPhoto2, 8031, null, null));

        // --- Afterparty sub-album ---
        $afterPhoto = $this->makePhoto(
            $afterparty,
            'gala-afterparty-1.jpg',
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
            'trip-1.jpg',
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
            'trip-2.jpg',
            '-3 months 15:30',
        );
        $manager->persist($tripPhoto2);
        $manager->persist(new Vote($tripPhoto2, $this->member(8028)));

        // --- Draft (unpublished) album ---
        $secretPhoto = $this->makePhoto(
            $secret,
            'secret-1.jpg',
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

        $manager->flush();
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
        string $resource,
        string $dateTime,
    ): Photo {
        $resourcePath = __DIR__ . '/resources/' . $resource;

        $dimensions = getimagesize($resourcePath);
        if (false === $dimensions) {
            throw new RuntimeException(sprintf('Cannot read fixture image "%s".', $resourcePath));
        }

        $stored = $this->fileStorage->store(
            StorageNamespace::PhotoOriginal,
            $resourcePath,
        );

        $photo = new Photo();
        $photo->setAlbum($album);
        $photo->setPath($stored->path);
        $photo->setDateTime(new DateTime($dateTime));
        // Aspect ratio is height / width, matching the pre-migration convention.
        $photo->setAspectRatio($dimensions[1] / $dimensions[0]);

        return $photo;
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
