<?php

declare(strict_types=1);

namespace App\Entity\Education\Enums;

use Symfony\Component\Translation\TranslatableMessage;

/**
 * How far a course document has got through rasterization. A document only becomes downloadable once it is Ready,
 * because a download is rebuilt from its pages and there is nothing to rebuild from before then.
 */
enum DocumentFlattenStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Ready = 'ready';
    case Failed = 'failed';

    public function label(): TranslatableMessage
    {
        return match ($this) {
            self::Pending => new TranslatableMessage('Waiting to be processed'),
            self::Processing => new TranslatableMessage('Being processed'),
            self::Ready => new TranslatableMessage('Ready'),
            self::Failed => new TranslatableMessage('Processing failed'),
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Pending, self::Processing => 'text-bg-secondary',
            self::Ready => 'text-bg-success',
            self::Failed => 'text-bg-danger',
        };
    }
}
