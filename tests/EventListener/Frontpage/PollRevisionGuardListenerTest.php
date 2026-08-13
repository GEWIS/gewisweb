<?php

declare(strict_types=1);

namespace App\Tests\EventListener\Frontpage;

use App\Entity\Frontpage\Poll;
use App\Entity\Frontpage\PollRevision;
use App\EventListener\Frontpage\PollRevisionGuardListener;
use App\Tests\Support\BuildsGuardEvents;
use PHPUnit\Framework\TestCase;
use stdClass;

use function implode;

/**
 * A poll has nothing to edit, which is what this guard is for: asking for changes would hand back a draft that does
 * not exist, and a second question on a poll members are already answering would replace it underneath them. Both
 * refusals carry their own message, which these pin.
 */
final class PollRevisionGuardListenerTest extends TestCase
{
    use BuildsGuardEvents;

    public function testBlocksRequestingChangesOnAPoll(): void
    {
        $event = $this->guardEvent(
            $this->revision(new Poll()),
            'request_changes',
            'in-review',
            'changes-requested',
        );

        new PollRevisionGuardListener()->onRequestChanges($event);

        self::assertTrue($event->isBlocked());
        self::assertStringContainsString(
            'sent back for changes',
            implode(
                "\n",
                $this->blockerMessages($event),
            ),
        );
    }

    public function testBlocksSubmittingOntoAPollThatIsAlreadyPublished(): void
    {
        $poll = new Poll();
        $poll->setLiveRevision($this->revision($poll));

        $event = $this->guardEvent(
            $this->revision($poll),
            'submit',
            'draft',
            'submitted',
        );

        new PollRevisionGuardListener()->onSubmit($event);

        self::assertTrue($event->isBlocked());
        self::assertStringContainsString(
            'already been published',
            implode(
                "\n",
                $this->blockerMessages($event),
            ),
        );
    }

    public function testAllowsTheFirstSubmissionOfAPoll(): void
    {
        $event = $this->guardEvent(
            $this->revision(new Poll()),
            'submit',
            'draft',
            'submitted',
        );

        new PollRevisionGuardListener()->onSubmit($event);

        self::assertFalse($event->isBlocked());
    }

    /**
     * Every other domain runs through the same workflow, so the guard has to leave subjects that are not its own
     * alone rather than block them all.
     */
    public function testIgnoresSubjectsFromOtherDomains(): void
    {
        $event = $this->guardEvent(
            new stdClass(),
            'request_changes',
            'in-review',
            'changes-requested',
        );

        new PollRevisionGuardListener()->onRequestChanges($event);

        self::assertFalse($event->isBlocked());
    }

    private function revision(Poll $poll): PollRevision
    {
        $revision = new PollRevision();
        $poll->addRevision($revision);

        return $revision;
    }
}
