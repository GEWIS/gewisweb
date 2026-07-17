<?php

declare(strict_types=1);

namespace App\Entity\Photo;

use App\Entity\Application\Traits\IdentifiableTrait;
use App\Entity\Application\Traits\TimestampableTrait;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Selectable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OrderBy;

/**
 * Album.
 */
#[Entity]
#[HasLifecycleCallbacks]
class Album
{
    use IdentifiableTrait;
    use TimestampableTrait;

    /**
     * First date of photos in album.
     */
    #[Column(
        type: Types::DATETIME_MUTABLE,
        nullable: true,
    )]
    private ?DateTime $startDateTime = null;

    /**
     * End date of photos in album.
     */
    #[Column(
        type: Types::DATETIME_MUTABLE,
        nullable: true,
    )]
    private ?DateTime $endDateTime = null;

    /**
     * Name of the album.
     */
    #[Column(type: Types::STRING)]
    private string $name;

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
    private ?Album $parent = null;

    /**
     * all the subalbums
     * Note: These are fetched extra lazy so we can efficiently retrieve an
     * album count.
     *
     * @var Collection<array-key, Album>&Selectable<array-key, Album>
     */
    #[OneToMany(
        targetEntity: self::class,
        mappedBy: 'parent',
        cascade: [
            'persist',
            'remove',
        ],
        fetch: 'EXTRA_LAZY',
    )]
    private Collection $children;

    /**
     * all the photo's in this album.
     * Note: These are fetched extra lazy so we can efficiently retrieve an
     * photo count.
     *
     * @var Collection<array-key, Photo>
     */
    #[OneToMany(
        targetEntity: Photo::class,
        mappedBy: 'album',
        cascade: [
            'persist',
            'remove',
        ],
        fetch: 'EXTRA_LAZY',
    )]
    #[OrderBy(value: ['dateTime' => 'ASC'])]
    protected Collection $photos;

    /**
     * The cover photo to display with the album.
     */
    #[Column(
        type: Types::STRING,
        nullable: true,
    )]
    private ?string $coverPath = null;

    /**
     * Whether the album is published.
     */
    #[Column(type: Types::BOOLEAN)]
    private bool $published = false;

    public function __construct()
    {
        $this->children = new ArrayCollection();
        $this->photos = new ArrayCollection();
    }

    /**
     * Gets an array of all child albums.
     *
     * @return Collection<array-key, Album>
     */
    public function getChildren(): Collection
    {
        return $this->children;
    }

    /**
     * Gets an array of all the photos in this album.
     *
     * @return Collection<array-key, Photo>
     */
    public function getPhotos(): Collection
    {
        return $this->photos;
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
     * Whether this album is published.
     */
    public function isPublished(): bool
    {
        return $this->published;
    }

    /**
     * Set the published state.
     */
    public function setPublished(bool $published): void
    {
        $this->published = $published;
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
     * The number of published sub-albums, for the public card count where drafts must not be counted. Matching on a
     * Criteria keeps this a COUNT query against the EXTRA_LAZY association rather than hydrating every child.
     */
    public function getPublishedAlbumCount(): int
    {
        return $this->children->matching(
            Criteria::create()->where(Criteria::expr()->eq('published', true)),
        )->count();
    }
}
