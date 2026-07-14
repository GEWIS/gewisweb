<?php

declare(strict_types=1);

namespace App\Message\Photo;

use App\Entity\Application\Enums\ImageProfile;

/**
 * Requests asynchronous pre-generation of every {@see ImageProfile} variant for a stored source image, so the variants
 * are ready before anyone requests them (the synchronous generate-on-miss path is only a safety net).
 */
class ProcessImageVariantsMessage
{
    public function __construct(
        private readonly string $sourcePath,
        private readonly ImageProfile $profile,
    ) {
    }

    public function getSourcePath(): string
    {
        return $this->sourcePath;
    }

    public function getProfile(): ImageProfile
    {
        return $this->profile;
    }
}
