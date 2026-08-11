<?php

declare(strict_types=1);

namespace App\Service\Application;

use App\Entity\Application\RevisionInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

/**
 * Finds the module that wants to be told about a revision. A domain that registered nothing raises no notification,
 * which is the right answer for one whose reviewers hear about submissions some other way.
 */
final readonly class RevisionNotificationRegistry
{
    /**
     * @param iterable<RevisionNotificationInterface> $notifications
     */
    public function __construct(
        #[AutowireIterator('app.revision_notification')]
        private iterable $notifications,
    ) {
    }

    public function for(RevisionInterface $revision): ?RevisionNotificationInterface
    {
        foreach ($this->notifications as $notification) {
            if ($notification->supports($revision)) {
                return $notification;
            }
        }

        return null;
    }
}
