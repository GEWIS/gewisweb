<?php

declare(strict_types=1);

namespace Photo\Model;

use Decision\Model\Organ as OrganModel;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\UniqueConstraint;
use InvalidArgumentException;

use function get_class;

/**
 * A tag in a photo for an organ (BM/GMM body).
 *
 * @extends Tag<OrganModel>
 */
#[Entity]
#[UniqueConstraint(fields: ['photo', 'body'])]
class BodyTag extends Tag
{
    #[ManyToOne(
        targetEntity: OrganModel::class,
        inversedBy: 'tags',
    )]
    #[JoinColumn(
        name: 'body_id',
        referencedColumnName: 'id',
        nullable: true,
    )]
    protected OrganModel $body;

    public function getTagged(): OrganModel
    {
        return $this->body;
    }

    /**
     * @psalm-param OrganModel $tagged
     */
    public function setTagged(TaggableInterface $tagged): void
    {
        if (!($tagged instanceof OrganModel)) {
            throw new InvalidArgumentException(sprintf('Expected Organ got %s...', get_class($tagged)));
        }

        $this->body = $tagged;
    }

    public function getType(): string
    {
        return 'organ';
    }
}
