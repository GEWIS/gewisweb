<?php

declare(strict_types=1);

namespace App\Entity\Decision;

use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;

/**
 * One uploaded file of a {@see MeetingDocument}.
 */
#[Entity]
#[HasLifecycleCallbacks]
class MeetingDocumentVersion extends AbstractDocumentVersion
{
    #[ManyToOne(
        targetEntity: MeetingDocument::class,
        inversedBy: 'versions',
    )]
    #[JoinColumn(
        name: 'document_id',
        nullable: false,
    )]
    private MeetingDocument $document;

    public function getDocument(): MeetingDocument
    {
        return $this->document;
    }

    public function setDocument(MeetingDocument $document): void
    {
        $document->addVersion($this);
        $this->document = $document;
    }
}
