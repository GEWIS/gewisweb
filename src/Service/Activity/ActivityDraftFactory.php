<?php

declare(strict_types=1);

namespace App\Service\Activity;

use App\Entity\Activity\Activity;
use App\Entity\Activity\ActivityLocalisedText;
use App\Entity\Activity\ActivityRevision;
use App\Entity\Activity\Enums\ActivityCategories;
use App\Entity\Decision\Member;

/**
 * Builds a blank activity draft: the four localised texts and the fields the mapping insists on, and nothing else.
 *
 * Two things start an activity off, the create form and a day being reserved in the option calendar, and both have to
 * hand the revision workflow the same shape. It lives here rather than in either of them so a third caller cannot get
 * it subtly wrong.
 */
final readonly class ActivityDraftFactory
{
    /**
     * A blank draft revision the create form can bind to.
     *
     * The schedule is deliberately left empty rather than pre-filled with "now": the form requires it, and a made-up
     * time is worse than an obvious gap.
     */
    public function newRevision(): ActivityRevision
    {
        $revision = new ActivityRevision();
        $revision->setName(new ActivityLocalisedText());
        $revision->setLocation(new ActivityLocalisedText());
        $revision->setCosts(new ActivityLocalisedText());
        $revision->setDescription(new ActivityLocalisedText());
        $revision->setCategory(ActivityCategories::Other);

        return $revision;
    }

    /**
     * A brand-new activity with its first draft attached, ready to be filled in.
     */
    public function newActivity(Member $creator): Activity
    {
        $activity = new Activity();
        $activity->setCreator($creator);

        $revision = $this->newRevision();
        $revision->setAuthor($creator);

        $activity->addRevision($revision);
        $activity->setCurrentRevision($revision);

        return $activity;
    }
}
