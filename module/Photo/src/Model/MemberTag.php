<?php

declare(strict_types=1);

namespace Photo\Model;

use Decision\Model\Member as MemberModel;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\UniqueConstraint;
use InvalidArgumentException;

use function get_class;

/**
 * A tag in a photo for a member.
 *
 * @extends Tag<MemberModel>
 */
#[Entity]
#[UniqueConstraint(fields: ['photo', 'member'])]
class MemberTag extends Tag
{
    #[ManyToOne(
        targetEntity: MemberModel::class,
        inversedBy: 'tags',
    )]
    #[JoinColumn(
        name: 'member_id',
        referencedColumnName: 'lidnr',
        nullable: true,
    )]
    protected MemberModel $member;

    public function getTagged(): MemberModel
    {
        return $this->member;
    }

    /**
     * @psalm-param MemberModel $tagged
     */
    public function setTagged(TaggableInterface $tagged): void
    {
        if (!($tagged instanceof MemberModel)) {
            throw new InvalidArgumentException(sprintf('Expected Member got %s...', get_class($tagged)));
        }

        $this->member = $tagged;
    }

    public function getType(): string
    {
        return 'member';
    }
}
