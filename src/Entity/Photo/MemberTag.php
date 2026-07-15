<?php

declare(strict_types=1);

namespace App\Entity\Photo;

use App\Entity\Decision\Member as MemberModel;
use App\Repository\Photo\MemberTagRepository;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;

/**
 * A tag identifying a member who appears in a photo. This is the GDPR-relevant tag subtype: the member's personal data
 * export walks these (never {@see OrganTag}s).
 *
 * @psalm-import-type PhotoGdprArrayType from Photo as ImportedPhotoGdprArrayType
 * @psalm-type MemberTagGdprArrayType = array{
 *     id: int,
 *     photo: ImportedPhotoGdprArrayType,
 * }
 */
#[Entity(repositoryClass: MemberTagRepository::class)]
class MemberTag extends Tag
{
    /**
     * The tagged member. The join column is nullable at the database level only so that {@see OrganTag} rows (which
     * have no member) coexist in the single table; a MemberTag always has a member.
     */
    #[ManyToOne(
        targetEntity: MemberModel::class,
        inversedBy: 'tags',
    )]
    #[JoinColumn(
        name: 'member_id',
        referencedColumnName: 'lidnr',
    )]
    private MemberModel $member;

    public function getMember(): MemberModel
    {
        return $this->member;
    }

    public function setMember(MemberModel $member): void
    {
        $this->member = $member;
    }

    /**
     * Returns the tag as an associative array.
     *
     * @return array{
     *     id: int,
     *     photo_id: int,
     *     member_id: int,
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'photo_id' => $this->getPhoto()->getId(),
            'member_id' => $this->getMember()->getLidnr(),
        ];
    }

    /**
     * @return MemberTagGdprArrayType
     */
    public function toGdprArray(): array
    {
        return [
            'id' => $this->getId(),
            'photo' => $this->getPhoto()->toGdprArray(),
        ];
    }
}
