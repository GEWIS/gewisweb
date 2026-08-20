<?php

declare(strict_types=1);

namespace App\EventListener\Activity;

use App\Entity\Activity\ActivityDateOption;
use App\Entity\Activity\ActivityLocalisedText;
use App\Entity\Activity\ActivityProposal;
use App\Entity\Activity\Enums\DateOptionStatus;
use App\Service\Activity\ActivityDraftFactory;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Workflow\Event\Event;

use function sprintf;

/**
 * Turns a reserved day into the activity it is meant to become.
 *
 * The whole point of connecting the two. A body that has just been given a day would otherwise have to go to another
 * screen and type its own proposal in again; instead the activity is already there as a draft, carrying the body, the
 * working title, the description and the days, and the body finishes it through the ordinary revision workflow. It is
 * also what the budget reminder measures against.
 *
 * The days carry no clock time, because the calendar reserves days rather than hours, so the draft opens at midnight
 * and the schedule is the first thing left to fill in. Inventing a time from "evening" would be making data up.
 */
#[AsEventListener(event: 'workflow.activity_proposal.entered.scheduled')]
final readonly class SeedActivityFromProposalListener
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ActivityDraftFactory $activityDraftFactory,
    ) {
    }

    /**
     * @param Event<object> $event
     */
    public function __invoke(Event $event): void
    {
        $proposal = $event->getSubject();

        if (!$proposal instanceof ActivityProposal) {
            return;
        }

        $chosen = $proposal->getChosenOption();

        // Scheduling without a day picked is meaningless; whoever applied the transition owes us one.
        if (null === $chosen) {
            return;
        }

        // The statuses are settled here rather than by whoever applied the transition, so a day reserved from the
        // queue, from a script or from a test all end up in the same state: the chosen day held, every other day the
        // body asked for released for whoever is next in line.
        $proposal->declineDateOptionsOtherThan($chosen);
        $chosen->setStatus(DateOptionStatus::Approved);

        // Reopening and scheduling again must not start a second activity.
        if (null !== $proposal->getActivity()) {
            return;
        }

        $activity = $this->activityDraftFactory->newActivity($proposal->getCreatedBy());
        $revision = $activity->getCurrentRevision();

        if (null === $revision) {
            return;
        }

        $revision->setOrgan($proposal->getOrgan());
        $revision->setName(new ActivityLocalisedText(
            $proposal->getName(),
            $proposal->getName(),
        ));

        $description = $proposal->getDescription();

        if (null !== $description) {
            $revision->setDescription(new ActivityLocalisedText(
                $description,
                $description,
            ));
        }

        $revision->setBeginTime($this->startOf($chosen));
        $revision->setEndTime($this->endOf($chosen));

        $this->entityManager->persist($activity);
        $proposal->setActivity($activity);
    }

    private function startOf(ActivityDateOption $option): DateTime
    {
        return new DateTime(sprintf(
            '%s 00:00:00',
            $option->getBeginsAt()->format('Y-m-d'),
        ));
    }

    private function endOf(ActivityDateOption $option): DateTime
    {
        return new DateTime(sprintf(
            '%s 23:59:59',
            $option->getEndsAt()->format('Y-m-d'),
        ));
    }
}
