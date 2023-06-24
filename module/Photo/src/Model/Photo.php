<?php

declare(strict_types=1);

namespace Photo\Model;

use Application\Model\Traits\IdentifiableTrait;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\ORM\Mapping\PrePersist;
use Laminas\Permissions\Acl\Resource\ResourceInterface;

use function getimagesize;

/**
 * Photo.
 *
 * @psalm-import-type AlbumArrayType from Album as ImportedAlbumArrayType
 * @psalm-type PhotoArrayType = array{
 *     id: int,
 *     dateTime: DateTime,
 *     artist: ?string,
 *     camera: ?string,
 *     flash: ?bool,
 *     focalLength: ?float,
 *     exposureTime: ?float,
 *     shutterSpeed: ?string,
 *     aperture: ?string,
 *     iso: ?int,
 *     album: ImportedAlbumArrayType,
 *     path: string,
 *     smallThumbPath: string,
 *     largeThumbPath: string,
 *     longitude: ?float,
 *     latitude: ?float,
 * }
 */
#[Entity]
#[HasLifecycleCallbacks]
class Photo implements ResourceInterface
{
    use IdentifiableTrait;

    /**
     * Date and time when the photo was taken.
     */
    #[Column(type: 'datetime')]
    protected DateTime $dateTime;

    /**
     * Artist/author.
     */
    #[Column(
        type: 'string',
        nullable: true,
    )]
    protected ?string $artist = null;

    /**
     * The type of camera used.
     */
    #[Column(
        type: 'string',
        nullable: true,
    )]
    protected ?string $camera = null;

    /**
     * Whether a flash has been used.
     */
    #[Column(
        type: 'boolean',
        nullable: true,
    )]
    protected ?bool $flash = null;

    /**
     * The focal length of the lens, in mm.
     */
    #[Column(
        type: 'float',
        nullable: true,
    )]
    protected ?float $focalLength = null;

    /**
     * The exposure time, in seconds.
     */
    #[Column(
        type: 'float',
        nullable: true,
    )]
    protected ?float $exposureTime = null;

    /**
     * The shutter speed.
     */
    #[Column(
        type: 'string',
        nullable: true,
    )]
    protected ?string $shutterSpeed = null;

    /**
     * The lens aperture.
     */
    #[Column(
        type: 'string',
        nullable: true,
    )]
    protected ?string $aperture = null;

    /**
     * Indicates the ISO Speed and ISO Latitude of the camera.
     */
    #[Column(
        type: 'smallint',
        nullable: true,
    )]
    protected ?int $iso = null;

    /**
     * Album in which the photo is.
     */
    #[ManyToOne(
        targetEntity: Album::class,
        inversedBy: 'photos',
    )]
    #[JoinColumn(
        name: 'album_id',
        referencedColumnName: 'id',
        nullable: false,
    )]
    protected Album $album;

    /**
     * The path where the photo is located relative to the storage directory.
     */
    #[Column(type: 'string')]
    protected string $path;

    /**
     * The path where the small thumbnail of the photo is located relative to
     * the storage directory.
     */
    #[Column(type: 'string')]
    protected string $smallThumbPath;

    /**
     * The path where the large thumbnail of the photo is located relative to
     * the storage directory.
     */
    #[Column(type: 'string')]
    protected string $largeThumbPath;

    /**
     * The GPS longitude of the location where the photo was taken.
     */
    #[Column(
        type: 'float',
        nullable: true,
    )]
    protected ?float $longitude = null;

    /**
     * The GPS latitude of the location where the photo was taken.
     */
    #[Column(
        type: 'float',
        nullable: true,
    )]
    protected ?float $latitude = null;

    /**
     * All the votes for this photo.
     *
     * @var Collection<array-key, Vote>
     */
    #[OneToMany(
        targetEntity: Vote::class,
        mappedBy: 'photo',
        cascade: ['persist', 'remove'],
    )]
    protected Collection $votes;

    /**
     * All the tags for this photo.
     *
     * @var Collection<array-key, Tag>
     */
    #[OneToMany(
        targetEntity: Tag::class,
        mappedBy: 'photo',
        cascade: ['persist', 'remove'],
    )]
    protected Collection $tags;

    /**
     * All the profile photos that use this photo.
     *
     * @var Collection<array-key, ProfilePhoto>
     */
    #[OneToMany(
        targetEntity: ProfilePhoto::class,
        mappedBy: 'photo',
        cascade: ['persist', 'remove'],
        fetch: 'EXTRA_LAZY',
    )]
    protected Collection $profilePhotos;

    /**
     * The corresponding WeeklyPhoto entity if this photo has been a weekly photo.
     */
    #[OneToOne(
        targetEntity: WeeklyPhoto::class,
        mappedBy: 'photo',
        cascade: ['persist', 'remove'],
    )]
    protected ?WeeklyPhoto $weeklyPhoto = null;

    /**
     * The aspect ratio of the photo width/height.
     */
    #[Column(
        type: 'float',
        nullable: true,
    )]
    protected ?float $aspectRatio = null;

    public function __construct()
    {
        $this->tags = new ArrayCollection();
        $this->votes = new ArrayCollection();
        $this->profilePhotos = new ArrayCollection();
    }

    /**
     * Get the date.
     */
    public function getDateTime(): DateTime
    {
        return $this->dateTime;
    }

    /**
     * Get the artist.
     */
    public function getArtist(): ?string
    {
        return $this->artist;
    }

    /**
     * Get the camera.
     */
    public function getCamera(): ?string
    {
        return $this->camera;
    }

    /**
     * Get the flash.
     */
    public function getFlash(): ?bool
    {
        return $this->flash;
    }

    /**
     * Get the focal length.
     */
    public function getFocalLength(): ?float
    {
        return $this->focalLength;
    }

    /**
     * Get the exposure time.
     */
    public function getExposureTime(): ?float
    {
        return $this->exposureTime;
    }

    /**
     * Get the shutter speed.
     */
    public function getShutterSpeed(): ?string
    {
        return $this->shutterSpeed;
    }

    /**
     * Get the aperture.
     */
    public function getAperture(): ?string
    {
        return $this->aperture;
    }

    /**
     * Get the ISO.
     */
    public function getIso(): ?int
    {
        return $this->iso;
    }

    /**
     * Get the album.
     */
    public function getAlbum(): Album
    {
        return $this->album;
    }

    /**
     * Get the path where the photo is stored.
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Get the path where the large thumbnail is stored.
     */
    public function getLargeThumbPath(): string
    {
        return $this->largeThumbPath;
    }

    /**
     * Get the path where the large thumbnail is stored.
     */
    public function getSmallThumbPath(): string
    {
        return $this->smallThumbPath;
    }

    /**
     * Get the GPS longitude of the location where the photo was taken.
     */
    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    /**
     * Get the GPS latitude of the location where the photo was taken.
     */
    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    /**
     * @return Collection<array-key, Tag>
     */
    public function getTags(): Collection
    {
        return $this->tags;
    }

    public function getTagCount(): int
    {
        return $this->tags->count();
    }

    public function getVoteCount(): int
    {
        return $this->votes->count();
    }

    public function getWeeklyPhoto(): ?WeeklyPhoto
    {
        return $this->weeklyPhoto;
    }

    public function getAspectRatio(): ?float
    {
        if (null === $this->aspectRatio) {
            $this->calculateAspectRatio();
        }

        return $this->aspectRatio;
    }

    #[PrePersist]
    public function calculateAspectRatio(): void
    {
        [$width, $height] = getimagesize('public/data/' . $this->getSmallThumbPath());
        $this->aspectRatio = $height / $width;
    }

    /**
     * Set the dateTime.
     */
    public function setDateTime(DateTime $dateTime): void
    {
        $this->dateTime = $dateTime;
    }

    /**
     * Set the artist.
     */
    public function setArtist(?string $artist): void
    {
        $this->artist = $artist;
    }

    /**
     * Set the camera.
     */
    public function setCamera(?string $camera): void
    {
        $this->camera = $camera;
    }

    /**
     * Set the flash.
     */
    public function setFlash(?bool $flash): void
    {
        $this->flash = $flash;
    }

    /**
     * Set the focal length.
     */
    public function setFocalLength(?float $focalLength): void
    {
        $this->focalLength = $focalLength;
    }

    /**
     * Set the exposure time.
     */
    public function setExposureTime(?float $exposureTime): void
    {
        $this->exposureTime = $exposureTime;
    }

    /**
     * Set the shutter speed.
     */
    public function setShutterSpeed(?string $shutterSpeed): void
    {
        $this->shutterSpeed = $shutterSpeed;
    }

    /**
     * Set the aperture.
     */
    public function setAperture(?string $aperture): void
    {
        $this->aperture = $aperture;
    }

    /**
     * Set the ISO.
     */
    public function setIso(?int $iso): void
    {
        $this->iso = $iso;
    }

    /**
     * Set the album.
     */
    public function setAlbum(Album $album): void
    {
        $this->album = $album;
    }

    /**
     * Set the path where the photo is stored.
     */
    public function setPath(string $path): void
    {
        $this->path = $path;
    }

    /**
     * Set the path where the large thumbnail is stored.
     */
    public function setLargeThumbPath(string $path): void
    {
        $this->largeThumbPath = $path;
    }

    /**
     * Set the path where the small thumbnail is stored.
     */
    public function setSmallThumbPath(string $path): void
    {
        $this->smallThumbPath = $path;
    }

    /**
     * Set the GPS longitude of the location where the photo was taken.
     */
    public function setLongitude(?float $longitude): void
    {
        $this->longitude = $longitude;
    }

    /**
     * Set the GPS latitude of the location where the photo was taken.
     */
    public function setLatitude(?float $latitude): void
    {
        $this->latitude = $latitude;
    }

    /**
     * Sets the aspect ratio.
     */
    public function setAspectRatio(?float $ratio): void
    {
        $this->aspectRatio = $ratio;
    }

    /**
     * Add a vote for this photo.
     */
    public function addVote(Vote $vote): void
    {
        $vote->setPhoto($this);
        $this->votes[] = $vote;
    }

    /**
     * Add a tag to a photo.
     */
    public function addTag(Tag $tag): void
    {
        $tag->setPhoto($this);
        $this->tags[] = $tag;
    }

    /**
     * @param ProfilePhoto[] $profilePhotos
     */
    public function addProfilePhotos(array $profilePhotos): void
    {
        foreach ($profilePhotos as $profilePhoto) {
            $this->addProfilePhoto($profilePhoto);
        }
    }

    public function addProfilePhoto(ProfilePhoto $profilePhoto): void
    {
        $profilePhoto->setPhoto($this);
        $this->profilePhotos->add($profilePhoto);
    }

    /**
     * @param ProfilePhoto[] $profilePhotos
     */
    public function removeProfilePhotos(array $profilePhotos): void
    {
        foreach ($profilePhotos as $profilePhoto) {
            $this->removeProfilePhoto($profilePhoto);
        }
    }

    public function removeProfilePhoto(ProfilePhoto $profilePhoto): void
    {
        $this->profilePhotos->removeElement($profilePhoto);
    }

    /**
     * @return Collection<array-key, ProfilePhoto>
     */
    public function getProfilePhotos(): Collection
    {
        return $this->profilePhotos;
    }

    /**
     * Returns an associative array representation of this object.
     *
     * @return PhotoArrayType
     */
    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'dateTime' => $this->getDateTime(),
            'artist' => $this->getArtist(),
            'camera' => $this->getCamera(),
            'flash' => $this->getFlash(),
            'focalLength' => $this->getFocalLength(),
            'exposureTime' => $this->getExposureTime(),
            'shutterSpeed' => $this->getShutterSpeed(),
            'aperture' => $this->getAperture(),
            'iso' => $this->getIso(),
            'album' => $this->getAlbum()->toArray(),
            'path' => $this->getPath(),
            'smallThumbPath' => $this->getSmallThumbPath(),
            'largeThumbPath' => $this->getLargeThumbPath(),
            'longitude' => $this->getLongitude(),
            'latitude' => $this->getLatitude(),
        ];
    }

    /**
     * Get the resource ID.
     */
    public function getResourceId(): string
    {
        return 'photo';
    }
}
