<?php

declare(strict_types=1);

namespace App\Entity\Photo;

use App\Entity\Decision\Organ;
use App\Repository\Photo\OrganTagRepository;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;

/**
 * A tag linking a photo to an organ it belongs to or features. Unlike {@see MemberTag} this carries no
 * personal data, so it is excluded from GDPR member exports; in the viewer it links through to the organ's page.
 */
#[Entity(repositoryClass: OrganTagRepository::class)]
class OrganTag extends Tag
{
    /**
     * The tagged organ. The join column is nullable at the database level only so that {@see MemberTag} rows coexist in
     * the single table; an OrganTag always has an organ.
     */
    #[ManyToOne(targetEntity: Organ::class)]
    #[JoinColumn(
        name: 'organ_id',
        referencedColumnName: 'id',
    )]
    private Organ $organ;

    public function getOrgan(): Organ
    {
        return $this->organ;
    }

    public function setOrgan(Organ $organ): void
    {
        $this->organ = $organ;
    }

    /**
     * Returns the tag as an associative array.
     *
     * @return array{
     *     id: int,
     *     photo_id: int,
     *     organ_id: int,
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'photo_id' => $this->getPhoto()->getId(),
            'organ_id' => $this->getOrgan()->getId(),
        ];
    }
}
