<?php

declare(strict_types=1);

namespace Photo\Model;

use Application\Model\Traits\IdentifiableTrait;
use Application\Model\Traits\TimestampableTrait;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OrderBy;
use Laminas\Permissions\Acl\Resource\ResourceInterface;

/**
 * Album.
 *
 * @psalm-import-type PhotoArrayType from Photo as ImportedPhotoArrayType
 * @psalm-type AlbumArrayType = array{
 *     id: int,
 *     startDateTime: ?DateTime,
 *     endDateTime: ?DateTime,
 *     name: string,
 *     parent: ?array<string, mixed>,
 *     children: array{},
 *     photos: array{},
 *     coverPath: ?string,
 *     photoCount: int,
 *     albumCount: int,
 * }
 */
#[Entity]
#[HasLifecycleCallbacks]
class Album implements ResourceInterface
{
    use IdentifiableTrait;
    use TimestampableTrait;

    /**
     * First date of photos in album.
     */
    #[Column(
        type: 'datetime',
        nullable: true,
    )]
    protected ?DateTime $startDateTime = null;

    /**
     * End date of photos in album.
     */
    #[Column(
        type: 'datetime',
        nullable: true,
    )]
    protected ?DateTime $endDateTime = null;

    /**
     * Name of the album.
     */
    #[Column(type: 'string')]
    protected string $name;

    /**
     * Parent album, null if there is no parent album.
     */
    #[ManyToOne(
        targetEntity: self::class,
        inversedBy: 'children',
    )]
    #[JoinColumn(
        name: 'parent_id',
        referencedColumnName: 'id',
    )]
    protected ?Album $parent = null;

    /**
     * all the subalbums
     * Note: These are fetched extra lazy so we can efficiently retrieve an
     * album count.
     *
     * @var Collection<Album>
     */
    #[OneToMany(
        targetEntity: self::class,
        mappedBy: 'parent',
        cascade: ['persist', 'remove'],
        fetch: 'EXTRA_LAZY',
    )]
    protected Collection $children;

    /**
     * all the photo's in this album.
     * Note: These are fetched extra lazy so we can efficiently retrieve an
     * photo count.
     *
     * @var Collection<Photo>
     */
    #[OneToMany(
        targetEntity: Photo::class,
        mappedBy: 'album',
        cascade: ['persist', 'remove'],
        fetch: 'EXTRA_LAZY',
    )]
    #[OrderBy(value: ['dateTime' => 'ASC'])]
    protected Collection $photos;

    /**
     * The cover photo to display with the album.
     */
    #[Column(
        type: 'string',
        nullable: true,
    )]
    protected ?string $coverPath = null;

    public function __construct()
    {
        $this->children = new ArrayCollection();
        $this->photos = new ArrayCollection();
    }

    /**
     * Gets an array of all child albums.
     *
     * @return Collection<Album>
     */
    public function getChildren(): Collection
    {
        return $this->children;
    }

    /**
     * Gets an array of all the photos in this album.
     *
     * @return Collection<Photo>
     */
    public function getPhotos(): Collection
    {
        return $this->photos;
    }

    /**
     * Add a photo to an album.
     */
    public function addPhoto(Photo $photo): void
    {
        $photo->setAlbum($this);
        $this->photos[] = $photo;
    }

    /**
     * Add a sub album to an album.
     */
    public function addAlbum(Album $album): void
    {
        $album->setParent($this);
        $this->children[] = $album;
    }

    /**
     * Remove a subalbum.
     */
    public function removeAlbum(?Album $album): void
    {
        if (!$this->children->contains($album)) {
            return;
        }

        $this->children->removeElement($album);
    }

    /**
     * Returns an associative array representation of this object
     * including all child objects.
     *
     * @return array{
     *     id: int,
     *     startDateTime: ?DateTime,
     *     endDateTime: ?DateTime,
     *     name: string,
     *     parent: ?array<string, mixed>,
     *     children: AlbumArrayType[],
     *     photos: ImportedPhotoArrayType[],
     *     coverPath: ?string,
     *     photoCount: int,
     *     albumCount: int,
     * }
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
     * @return AlbumArrayType
     */
    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'startDateTime' => $this->getStartDateTime(),
            'endDateTime' => $this->getEndDateTime(),
            'name' => $this->getName(),
            'parent' => $this->getParent()?->toArray(),
            'children' => [],
            'photos' => [],
            'coverPath' => $this->getCoverPath(),
            'photoCount' => $this->getPhotoCount(),
            'albumCount' => $this->getAlbumCount(),
        ];
    }

    /**
     * Get the start date.
     */
    public function getStartDateTime(): ?DateTime
    {
        return $this->startDateTime;
    }

    /**
     * Set the start date.
     */
    public function setStartDateTime(?DateTime $startDateTime): void
    {
        $this->startDateTime = $startDateTime;
    }

    /**
     * Get the end date.
     */
    public function getEndDateTime(): ?DateTime
    {
        return $this->endDateTime;
    }

    /**
     * Set the end date.
     */
    public function setEndDateTime(?DateTime $endDateTime): void
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
     */
    public function setParent(?Album $parent): void
    {
        $this->parent = $parent;
    }

    /**
     * Get the album cover.
     */
    public function getCoverPath(): ?string
    {
        return $this->coverPath;
    }

    /**
     * Set the cover photo for the album.
     */
    public function setCoverPath(?string $photo): void
    {
        $this->coverPath = $photo;
    }

    /**
     * Get the amount of photos in the album.
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
     */
    public function getAlbumCount(): int
    {
        return $this->children->count();
    }

    /**
     * Get the resource ID.
     */
    public function getResourceId(): string
    {
        return 'album';
    }
}
