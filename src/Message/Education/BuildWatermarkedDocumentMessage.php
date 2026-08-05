<?php

declare(strict_types=1);

namespace App\Message\Education;

/**
 * The watermark names who asked and when, so the result belongs to one request and is never reused for another.
 */
class BuildWatermarkedDocumentMessage
{
    public function __construct(private readonly int $downloadId)
    {
    }

    public function getDownloadId(): int
    {
        return $this->downloadId;
    }
}
