<?php

namespace Photo\Model;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping\{Column,
    Entity,
    GeneratedValue,
    HasLifecycleCallbacks,
    Id,
    JoinColumn,
    ManyToOne,
    OneToMany,
    OrderBy,
};
use Laminas\Permissions\Acl\Resource\ResourceInterface;

/**
 * Album.
 */
#[Entity]
#[HasLifecycleCallbacks]
class Album implements ResourceInterface
{
    /**
     * Album ID.
     */
    #[Id]
    #[Column(type: "integer")]
    #[GeneratedValue(strategy: "AUTO")]
    protected int $id;

    /**
     * First date of photos in album.
     */
    #[Column(
        type: "datetime",
        nullable: true,
    )]
    protected ?DateTime $startDateTime = null;

    /**
     * End date of photos in album.
     */
    #[Column(
        type: "datetime",
        nullable: true,
    )]
    protected ?DateTime $endDateTime = null;

    /**
     * Name of the album.
     */
    #[Column(type: "string")]
    protected string $name;

    /**
     * Parent album, null if there is no parent album.
     */
    #[ManyToOne(
        targetEntity: "Photo\Model\Album",
        inversedBy: "children",
    )]
    #[JoinColumn(
        name: "parent_id",
        referencedColumnName: "id",
    )]
    protected ?Album $parent;

    /**
     * all the subalbums
     * Note: These are fetched extra lazy so we can efficiently retrieve an
     * album count.
     */
    #[OneToMany(
        targetEntity: "Photo\Model\Album",
        mappedBy: "parent",
        cascade: ["persist", "remove"],
        fetch: "EXTRA_LAZY",
    )]
    protected ArrayCollection $children;

    /**
     * all the photo's in this album.
     * Note: These are fetched extra lazy so we can efficiently retrieve an
     * photo count.
     */
    #[OneToMany(
        targetEntity: "Photo\Model\Photo",
        mappedBy: "album",
        cascade: ["persist", "remove"],
        fetch: "EXTRA_LAZY",
    )]
    #[OrderBy(value: ["dateTime" => "ASC"])]
    protected ArrayCollection $photos;

    /**
     * The cover photo to display with the album.
     *
     * @ORM\Column(type="string", nullable=true)
     */
    #[Column(
        type: "string",
        nullable: true,
    )]
    protected ?string $coverPath;

    public function __construct()
    {
        $this->children = new ArrayCollection();
        $this->photos = new ArrayCollection();
    }

    /**
     * Gets an array of all child albums.
     *
     * @return ArrayCollection
     */
    public function getChildren(): ArrayCollection
    {
        return $this->children->toArray();
    }

    /**
     * Gets an array of all the photos in this album.
     *
     * @return ArrayCollection
     */
    public function getPhotos(): ArrayCollection
    {
        return $this->photos;
    }

    /**
     * Add a photo to an album.
     *
     * @param Photo $photo
     */
    public function addPhoto(Photo $photo): void
    {
        $photo->setAlbum($this);
        $this->photos[] = $photo;
    }

    /**
     * Add a sub album to an album.
     *
     * @param Album $album
     */
    public function addAlbum(Album $album): void
    {
        $album->setParent($this);
        $this->children[] = $album;
    }

    /**
     * Returns an associative array representation of this object
     * including all child objects.
     *
     * @return array
     */
    public function toArrayWithChildren(): array
    {
        $array = $this->toArray();
        foreach ($this->photos as $photo) {
            $array['photos'][] = $photo->toArray();
        }
        foreach ($this->children as $album) {
            $array['children'][] = $album->toArray();
        }

        return $array;
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
            'startDateTime' => $this->getStartDateTime(),
            'endDateTime' => $this->getEndDateTime(),
            'name' => $this->getName(),
            'parent' => is_null($this->getParent()) ? null
                : $this->getParent()->toArray(),
            'children' => [],
            'photos' => [],
            'coverPath' => $this->getCoverPath(),
            'photoCount' => $this->getPhotoCount(),
            'albumCount' => $this->getAlbumCount(),
        ];
    }

    /**
     * Get the ID.
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Get the start date.
     *
     * @return DateTime|null
     */
    public function getStartDateTime(): ?DateTime
    {
        return $this->startDateTime;
    }

    /**
     * Set the start date.
     */
    public function setStartDateTime(DateTime $startDateTime): void
    {
        $this->startDateTime = $startDateTime;
    }

    /**
     * Get the end date.
     *
     * @return DateTime|null
     */
    public function getEndDateTime(): ?DateTime
    {
        return $this->endDateTime;
    }

    /**
     * Set the end date.
     */
    public function setEndDateTime(DateTime $endDateTime): void
    {
        $this->endDateTime = $endDateTime;
    }

    /**
     * Get the album name.
     *
     * @return string $name
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set the name of the album.
     *
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * Get the parent album.
     *
     * @return Album|null $parent
     */
    public function getParent(): ?Album
    {
        return $this->parent;
    }

    /**
     * Set the parent of the album.
     *
     * @param Album $parent
     */
    public function setParent(Album $parent): void
    {
        $this->parent = $parent;
    }

    /**
     * Get the album cover.
     *
     * @return string|null
     */
    public function getCoverPath(): ?string
    {
        return $this->coverPath;
    }

    /**
     * Set the cover photo for the album.
     *
     * @param string $photo
     */
    public function setCoverPath(string $photo): void
    {
        $this->coverPath = $photo;
    }

    /**
     * Get the amount of photos in the album.
     *
     * @param bool $includeSubAlbums
     *
     * @return int
     */
    public function getPhotoCount(bool $includeSubAlbums = true): int
    {
        $count = $this->photos->count();

        if ($includeSubAlbums) {
            foreach ($this->children as $album) {
                $count += $album->getPhotoCount();
            }
        }

        return $count;
    }

    /**
     * Get the amount of subalbums in the album.
     *
     * @return int
     */
    public function getAlbumCount(): int
    {
        return $this->children->count();
    }

    /**
     * Get the resource ID.
     *
     * @return string
     */
    public function getResourceId(): string
    {
        return 'album';
    }
}
