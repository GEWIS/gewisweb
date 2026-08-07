<?php

declare(strict_types=1);

namespace App\Message\Education;

/**
 * Downloads are rebuilt from the page images, so a document is not downloadable until this has run.
 */
class FlattenCourseDocumentMessage
{
    public function __construct(private readonly int $documentId)
    {
    }

    public function getDocumentId(): int
    {
        return $this->documentId;
    }
}
