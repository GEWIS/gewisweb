<?php

declare(strict_types=1);

namespace App\Entity\Photo;

use App\Entity\Application\Traits\IdentifiableTrait;
use App\Repository\Photo\TagRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\DiscriminatorColumn;
use Doctrine\ORM\Mapping\DiscriminatorMap;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\InheritanceType;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;
use InvalidArgumentException;

use function sprintf;

/**
 * A tag placed on a photo. Single-table inheritance splits it into a {@see MemberTag} (a member appearing in the photo)
 * and an {@see OrganTag} (an organ the photo belongs to or features). A tag optionally carries a normalized
 * point-in-image position (`positionX`/`positionY` in the range [0, 1]); a whole-photo tag leaves both null.
 *
 * Both `member_id` and `organ_id` live on the single `Tag` table (one is always NULL for a given row). MariaDB treats
 * NULLs as distinct in a unique index, so `UNIQUE(photo_id, member_id)` and `UNIQUE(photo_id, organ_id)` coexist:
 * a member row (NULL organ_id) and an organ row (NULL member_id) never collide.
 */
#[Entity(repositoryClass: TagRepository::class)]
#[InheritanceType(value: 'SINGLE_TABLE')]
#[DiscriminatorColumn(
    name: 'dtype',
    type: Types::STRING,
)]
#[DiscriminatorMap(
    value: [
        'member' => MemberTag::class,
        'organ' => OrganTag::class,
    ],
)]
#[Table(name: 'Tag')]
#[UniqueConstraint(
    name: 'tag_member_uniq',
    columns: [
        'photo_id',
        'member_id',
    ],
)]
#[UniqueConstraint(
    name: 'tag_organ_uniq',
    columns: [
        'photo_id',
        'organ_id',
    ],
)]
abstract class Tag
{
    use IdentifiableTrait;

    #[ManyToOne(
        targetEntity: Photo::class,
        inversedBy: 'tags',
    )]
    #[JoinColumn(
        name: 'photo_id',
        referencedColumnName: 'id',
        nullable: false,
    )]
    private Photo $photo;

    /**
     * Normalized horizontal position of the tag marker within the photo (0 = left edge, 1 = right edge), or null for a
     * whole-photo tag.
     */
    #[Column(
        type: Types::FLOAT,
        nullable: true,
    )]
    private ?float $positionX = null;

    /**
     * Normalized vertical position of the tag marker within the photo (0 = top edge, 1 = bottom edge), or null for a
     * whole-photo tag.
     */
    #[Column(
        type: Types::FLOAT,
        nullable: true,
    )]
    private ?float $positionY = null;

    public function getPhoto(): Photo
    {
        return $this->photo;
    }

    public function setPhoto(Photo $photo): void
    {
        $this->photo = $photo;
    }

    public function getPositionX(): ?float
    {
        return $this->positionX;
    }

    public function getPositionY(): ?float
    {
        return $this->positionY;
    }

    /**
     * Whether this tag is pinned to a specific point in the image (rather than tagging the whole photo).
     */
    public function hasPosition(): bool
    {
        return null !== $this->positionX
            && null !== $this->positionY;
    }

    /**
     * Set (or clear, by passing null for both) the point-in-image position. Both coordinates must be given together and
     * lie within the normalized [0, 1] range.
     */
    public function setPosition(
        ?float $x,
        ?float $y,
    ): void {
        if (
            (null === $x) !== (null === $y)
        ) {
            throw new InvalidArgumentException('A tag position needs both coordinates or neither.');
        }

        if (
            null !== $x
            && null !== $y
            && (
                $x < 0.0
                || $x > 1.0
                || $y < 0.0
                || $y > 1.0
            )
        ) {
            throw new InvalidArgumentException(sprintf(
                'Tag position (%f, %f) is outside the normalized [0, 1] range.',
                $x,
                $y,
            ));
        }

        $this->positionX = $x;
        $this->positionY = $y;
    }
}
