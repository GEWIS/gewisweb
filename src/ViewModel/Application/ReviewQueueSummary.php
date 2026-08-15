<?php

declare(strict_types=1);

namespace App\ViewModel\Application;

use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * One review queue as the administration dashboard shows it: what is waiting and where to go and deal with it. It
 * carries the queue itself, because the dashboard shows every queue as one list and lets the reader narrow it to a
 * single one.
 */
final readonly class ReviewQueueSummary
{
    /**
     * @param list<ReviewQueueRow> $rows the queue itself, oldest first, which every domain's repository answers with
     */
    public function __construct(
        public string $key,
        public TranslatableInterface $name,
        public string $icon,
        public string $queueRoute,
        public array $rows,
    ) {
    }
}
