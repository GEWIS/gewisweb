<?php

declare(strict_types=1);

namespace App\Repository\User;

use App\Entity\Application\Enums\NotificationEmailFrequency;
use App\Entity\Application\Enums\NotificationType;
use App\Entity\User\NotificationEmailSubscription;
use App\Entity\User\User;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

use function array_map;

/**
 * @template-extends ServiceEntityRepository<NotificationEmailSubscription>
 */
class NotificationEmailSubscriptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            NotificationEmailSubscription::class,
        );
    }

    /**
     * The members who opted into email for the given category.
     *
     * @return User[]
     */
    public function findSubscribedUsers(NotificationType $category): array
    {
        return array_map(
            static fn (NotificationEmailSubscription $subscription): User => $subscription->getUser(),
            $this->findBy(['category' => $category]),
        );
    }

    /**
     * A member's email opt-ins, one per enabled category.
     *
     * @return NotificationEmailSubscription[]
     */
    public function findForUser(User $user): array
    {
        return $this->findBy(['user' => $user]);
    }

    /**
     * Bring the member's email opt-ins in line with the given per-category frequencies, adding, updating and removing
     * rows as needed. A category absent from the map is removed. A newly added category has its digest clock started
     * now, so an hourly, daily or weekly choice waits a full interval before its first email rather than mailing the
     * next notification straight away. The caller flushes.
     *
     * @param array<string, NotificationEmailFrequency> $frequencies keyed by {@see NotificationType} value
     */
    public function setForUser(
        User $user,
        array $frequencies,
    ): void {
        $entityManager = $this->getEntityManager();

        $seen = [];
        foreach ($this->findBy(['user' => $user]) as $subscription) {
            $value = $subscription->getCategory()->value;
            if (isset($frequencies[$value])) {
                $subscription->setFrequency($frequencies[$value]);
                $seen[$value] = true;
            } else {
                $entityManager->remove($subscription);
            }
        }

        foreach ($frequencies as $value => $frequency) {
            if (isset($seen[$value])) {
                continue;
            }

            $subscription = new NotificationEmailSubscription(
                $user,
                NotificationType::from($value),
                $frequency,
            );
            $subscription->setLastSentAt(new DateTimeImmutable());
            $entityManager->persist($subscription);
        }
    }
}
