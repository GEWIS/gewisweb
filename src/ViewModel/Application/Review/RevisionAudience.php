<?php

declare(strict_types=1);

namespace App\ViewModel\Application\Review;

/**
 * Who a section or a field is meant for. An author sees what everybody sees; a reviewer sees that and the material
 * that only makes sense while deciding.
 */
enum RevisionAudience: string
{
    case Everyone = 'everyone';
    case ReviewerOnly = 'reviewer';

    /**
     * Whether somebody looking with this audience is shown material meant for {@see $material}.
     */
    public function canSee(self $material): bool
    {
        return self::ReviewerOnly === $this
            || self::Everyone === $material;
    }
}
