<?php

namespace Photo\Model;

use DateTime;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\{Column,
    Entity,
    GeneratedValue,
    HasLifecycleCallbacks,
    Id,
    JoinColumn,
    ManyToOne,
    OneToMany,
    OneToOne,
};
use Laminas\Permissions\Acl\Resource\ResourceInterface;

/**
 * Photo.
 */
#[Entity]
#[HasLifecycleCallbacks]
class Photo implements ResourceInterface
{
    /**
     * Photo ID.
     */
    #[Id]
    #[Column(type: "integer")]
    #[GeneratedValue(strategy: "AUTO")]
    protected ?int $id = null;

    /**
     * Date and time when the photo was taken.
     */
    #[Column(type: "datetime")]
    protected DateTime $dateTime;

    /**
     * Artist/author.
     */
    #[Column(
        type: "string",
        nullable: true,
    )]
    protected ?string $artist = null;

    /**
     * The type of camera used.
     */
    #[Column(
        type: "string",
        nullable: true,
    )]
    protected ?string $camera = null;

    /**
     * Whether a flash has been used.
     */
    #[Column(
        type: "boolean",
        nullable: true,
    )]
    protected ?bool $flash = null;

    /**
     * The focal length of the lens, in mm.
     */
    #[Column(
        type: "float",
        nullable: true,
    )]
    protected ?float $focalLength = null;

    /**
     * The exposure time, in seconds.
     */
    #[Column(
        type: "float",
        nullable: true,
    )]
    protected ?float $exposureTime = null;

    /**
     * The shutter speed.
     */
    #[Column(
        type: "string",
        nullable: true,
    )]
    protected ?string $shutterSpeed = null;

    /**
     * The lens aperture.
     */
    #[Column(
        type: "string",
        nullable: true,
    )]
    protected ?string $aperture = null;

    /**
     * Indicates the ISO Speed and ISO Latitude of the camera.
     */
    #[Column(
        type: "smallint",
        nullable: true,
    )]
    protected ?int $iso = null;

    /**
     * Album in which the photo is.
     */
    #[ManyToOne(
        targetEntity: Album::class,
        inversedBy: "photos",
    )]
    #[JoinColumn(
        name: "album_id",
        referencedColumnName: "id",
        nullable: false,
    )]
    protected Album $album;

    /**
     * The path where the photo is located relative to the storage directory.
     */
    #[Column(type: "string")]
    protected string $path;

    /**
     * The path where the small thumbnail of the photo is located relative to
     * the storage directory.
     */
    #[Column(type: "string")]
    protected string $smallThumbPath;

    /**
     * The path where the large thumbnail of the photo is located relative to
     * the storage directory.
     */
    #[Column(type: "string")]
    protected string $largeThumbPath;

    /**
     * The GPS longitude of the location where the photo was taken.
     */
    #[Column(
        type: "float",
        nullable: true,
    )]
    protected ?float $longitude = null;

    /**
     * The GPS latitude of the location where the photo was taken.
     */
    #[Column(
        type: "float",
        nullable: true,
    )]
    protected ?float $latitude = null;

    /**
     * All the hits of this photo.
     */
    #[OneToMany(
        targetEntity: Hit::class,
        mappedBy: "photo",
        cascade: ["persist", "remove"],
    )]
    protected Collection $hits;

    /**
     * All the votes for this photo.
     */
    #[OneToMany(
        targetEntity: Vote::class,
        mappedBy: "photo",
        cascade: ["persist", "remove"],
    )]
    protected Collection $votes;

    /**
     * All the tags for this photo.
     */
    #[OneToMany(
        targetEntity: Tag::class,
        mappedBy: "photo",
        cascade: ["persist", "remove"],
        fetch: "EAGER",
    )]
    protected Collection $tags;

    /**
     * The corresponding WeeklyPhoto entity if this photo has been a weekly photo.
     */
    #[OneToOne(
        targetEntity: WeeklyPhoto::class,
        mappedBy: "photo",
        cascade: ["persist", "remove"],
    )]
    protected ?WeeklyPhoto $weeklyPhoto = null;

    /**
     * The aspect ratio of the photo width/height.
     */
    #[Column(
        type: "float",
        nullable: true,
    )]
    protected ?float $aspectRatio = null;

    /**
     * Get the ID.
     *
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Get the date.
     *
     * @return DateTime
     */
    public function getDateTime(): DateTime
    {
        return $this->dateTime;
    }

    /**
     * Get the artist.
     *
     * @return string|null
     */
    public function getArtist(): ?string
    {
        return $this->artist;
    }

    /**
     * Get the camera.
     *
     * @return string|null
     */
    public function getCamera(): ?string
    {
        return $this->camera;
    }

    /**
     * Get the flash.
     *
     * @return bool|null
     */
    public function getFlash(): ?bool
    {
        return $this->flash;
    }

    /**
     * Get the focal length.
     *
     * @return float|null
     */
    public function getFocalLength(): ?float
    {
        return $this->focalLength;
    }

    /**
     * Get the exposure time.
     *
     * @return float|null
     */
    public function getExposureTime(): ?float
    {
        return $this->exposureTime;
    }

    /**
     * Get the shutter speed.
     *
     * @return string|null
     */
    public function getShutterSpeed(): ?string
    {
        return $this->shutterSpeed;
    }

    /**
     * Get the aperture.
     *
     * @return string|null
     */
    public function getAperture(): ?string
    {
        return $this->aperture;
    }

    /**
     * Get the ISO.
     *
     * @return int|null
     */
    public function getIso(): ?int
    {
        return $this->iso;
    }

    /**
     * Get the album.
     *
     * @return Album
     */
    public function getAlbum(): Album
    {
        return $this->album;
    }

    /**
     * Get the path where the photo is stored.
     *
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Get the path where the large thumbnail is stored.
     *
     * @return string
     */
    public function getLargeThumbPath(): string
    {
        return $this->largeThumbPath;
    }

    /**
     * Get the path where the large thumbnail is stored.
     *
     * @return string
     */
    public function getSmallThumbPath(): string
    {
        return $this->smallThumbPath;
    }

    /**
     * Get the GPS longitude of the location where the photo was taken.
     *
     * @return float|null
     */
    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    /**
     * Get the GPS latitude of the location where the photo was taken.
     *
     * @return float|null
     */
    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    /**
     * @return Collection
     */
    public function getTags(): Collection
    {
        return $this->tags;
    }

    /**
     * @return int
     */
    public function getTagCount(): int
    {
        return $this->tags->count();
    }

    /**
     * @return int
     */
    public function getHitCount(): int
    {
        return $this->hits->count();
    }

    /**
     * @return int
     */
    public function getVoteCount(): int
    {
        return $this->votes->count();
    }

    /**
     * @return WeeklyPhoto|null
     */
    public function getWeeklyPhoto(): ?WeeklyPhoto
    {
        return $this->weeklyPhoto;
    }

    /**
     * @return float|null
     */
    public function getAspectRatio(): ?float
    {
        if (null === $this->aspectRatio) {
            [$width, $height, $type, $attr] = getimagesize('public/data/' . $this->getSmallThumbPath());
            $this->aspectRatio = $height / $width;
        }

        return $this->aspectRatio;
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
     *
     * @param string|null $artist
     */
    public function setArtist(?string $artist): void
    {
        $this->artist = $artist;
    }

    /**
     * Set the camera.
     *
     * @param string|null $camera
     */
    public function setCamera(?string $camera): void
    {
        $this->camera = $camera;
    }

    /**
     * Set the flash.
     *
     * @param bool|null $flash
     */
    public function setFlash(?bool $flash): void
    {
        $this->flash = $flash;
    }

    /**
     * Set the focal length.
     *
     * @param string|null $focalLength
     */
    public function setFocalLength(?string $focalLength): void
    {
        $this->focalLength = $focalLength;
    }

    /**
     * Set the exposure time.
     *
     * @param string|null $exposureTime
     */
    public function setExposureTime(?string $exposureTime): void
    {
        $this->exposureTime = $exposureTime;
    }

    /**
     * Set the shutter speed.
     *
     * @param string|null $shutterSpeed
     */
    public function setShutterSpeed(?string $shutterSpeed): void
    {
        $this->shutterSpeed = $shutterSpeed;
    }

    /**
     * Set the aperture.
     *
     * @param string|null $aperture
     */
    public function setAperture(?string $aperture): void
    {
        $this->aperture = $aperture;
    }

    /**
     * Set the ISO.
     *
     * @param int|null $iso
     */
    public function setIso(?int $iso): void
    {
        $this->iso = $iso;
    }

    /**
     * Set the album.
     *
     * @param Album $album
     */
    public function setAlbum(Album $album): void
    {
        $this->album = $album;
    }

    /**
     * Set the path where the photo is stored.
     *
     * @param string $path
     */
    public function setPath(string $path): void
    {
        $this->path = $path;
    }

    /**
     * Set the path where the large thumbnail is stored.
     *
     * @param string $path
     */
    public function setLargeThumbPath(string $path): void
    {
        $this->largeThumbPath = $path;
    }

    /**
     * Set the path where the small thumbnail is stored.
     *
     * @param string $path
     */
    public function setSmallThumbPath(string $path): void
    {
        $this->smallThumbPath = $path;
    }

    /**
     * Set the GPS longitude of the location where the photo was taken.
     *
     * @param float|null $longitude
     */
    public function setLongitude(?float $longitude): void
    {
        $this->longitude = $longitude;
    }

    /**
     * Set the GPS latitude of the location where the photo was taken.
     *
     * @param float|null $latitude
     */
    public function setLatitude(?float $latitude): void
    {
        $this->latitude = $latitude;
    }

    /**
     * Sets the aspect ratio.
     *
     * @param float|null $ratio
     */
    public function setAspectRatio(?float $ratio): void
    {
        $this->aspectRatio = $ratio;
    }

    /**
     * Add a hit to a photo.
     *
     * @param Hit $hit
     */
    public function addHit(Hit $hit): void
    {
        $hit->setPhoto($this);
        $this->hits[] = $hit;
    }

    /**
     * Add a vote for this photo.
     *
     * @param Vote @vote
     */
    public function addVote(Vote $vote): void
    {
        $vote->setPhoto($this);
        $this->votes[] = $vote;
    }
    /**
     * Add a tag to a photo.
     *
     * @param Tag $tag
     */
    public function addTag(Tag $tag): void
    {
        $tag->setPhoto($this);
        $this->tags[] = $tag;
    }

    /**
     * Returns an associative array representation of this object.
     *
     * @return array
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
     *
     * @return string
     */
    public function getResourceId(): string
    {
        return 'photo';
    }
}
