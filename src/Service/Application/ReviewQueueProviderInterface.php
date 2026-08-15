<?php

declare(strict_types=1);

namespace App\Service\Application;

use App\Entity\User\Enums\UserRoles;
use App\ViewModel\Application\ReviewQueueSummary;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * A domain's review queue, said once: who may deal with it, how it presents itself and what is waiting in it. The
 * administration dashboard folds every tagged queue into one list, and the domain's own approvals index shows the same
 * rows, so neither can drift from the other. Tag priority orders the queues on the dashboard.
 */
#[AutoconfigureTag('app.review_queue')]
interface ReviewQueueProviderInterface
{
    /**
     * The role a reader needs before this queue is theirs.
     */
    public function role(): UserRoles;

    /**
     * The queue itself, rows loaded. Called only after the role check, so a reader without the role costs no query.
     */
    public function queue(): ReviewQueueSummary;
}
