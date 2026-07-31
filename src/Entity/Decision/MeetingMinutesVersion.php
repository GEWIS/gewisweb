<?php

declare(strict_types=1);

namespace App\Entity\Decision;

use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;

/**
 * One uploaded file of a meeting's {@see MeetingMinutes}.
 */
#[Entity]
#[HasLifecycleCallbacks]
class MeetingMinutesVersion extends AbstractDocumentVersion
{
    #[ManyToOne(
        targetEntity: MeetingMinutes::class,
        inversedBy: 'versions',
    )]
    #[JoinColumn(
        name: 'meeting_type',
        referencedColumnName: 'meeting_type',
        nullable: false,
    )]
    #[JoinColumn(
        name: 'meeting_number',
        referencedColumnName: 'meeting_number',
        nullable: false,
    )]
    private MeetingMinutes $minutes;

    public function getMinutes(): MeetingMinutes
    {
        return $this->minutes;
    }

    public function setMinutes(MeetingMinutes $minutes): void
    {
        $minutes->addVersion($this);
        $this->minutes = $minutes;
    }
}
