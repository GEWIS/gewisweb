<?php

declare(strict_types=1);

namespace App\Tests\Service\Application;

use App\Entity\Application\Enums\RevisionStatus;
use App\Entity\Application\RevisableInterface;
use App\Entity\Application\RevisionInterface;
use App\Service\Application\RevisionActionResolver;
use App\ViewModel\Application\RevisionActions;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Workflow\Transition;
use Symfony\Component\Workflow\WorkflowInterface;

use function array_map;

/**
 * The two predicates that decide how a review screen behaves. Both used to be re-derived inline in four controllers,
 * where neither could be tested; the matrices below are what those copies were meant to agree on.
 */
final class RevisionActionResolverTest extends TestCase
{
    public function testTheEnabledTransitionsAreTakenFromTheWorkflow(): void
    {
        $actions = $this->resolve(
            RevisionStatus::Submitted,
            transitions: [
                'start_review',
                'reject',
            ],
        );

        self::assertSame(
            [
                'start_review',
                'reject',
            ],
            $actions->enabledTransitions,
        );
        self::assertSame(
            [
                'enabled_transitions' => [
                    'start_review',
                    'reject',
                ],
                'resubmission' => false,
            ],
            $actions->toFormOptions(),
        );
    }

    /**
     * Only a draft that answers a "changes requested" review is a resubmission; a draft spawned off an approved
     * revision is an ordinary edit and needs no response.
     */
    public function testOnlyADraftAnsweringRequestedChangesCountsAsAResubmission(): void
    {
        self::assertTrue($this->resolve(
            RevisionStatus::Draft,
            previous: RevisionStatus::ChangesRequested,
        )->isResubmission);

        self::assertFalse($this->resolve(
            RevisionStatus::Draft,
            previous: RevisionStatus::Approved,
        )->isResubmission);

        self::assertFalse($this->resolve(
            RevisionStatus::Draft,
            previous: null,
        )->isResubmission);

        self::assertFalse($this->resolve(
            RevisionStatus::Submitted,
            previous: RevisionStatus::ChangesRequested,
        )->isResubmission);
    }

    /**
     * Discarding falls back to what is live, so it needs something to fall back to and it needs the draft not to be
     * that thing itself.
     */
    public function testOnlyADraftWithSomethingLiveBehindItCanBeDiscarded(): void
    {
        self::assertTrue($this->resolve(
            RevisionStatus::Draft,
            hasOtherLiveRevision: true,
        )->isDiscardable);

        self::assertFalse($this->resolve(
            RevisionStatus::Draft,
            hasOtherLiveRevision: false,
        )->isDiscardable);

        self::assertFalse($this->resolve(
            RevisionStatus::Draft,
            isItselfLive: true,
        )->isDiscardable);

        self::assertFalse($this->resolve(
            RevisionStatus::Approved,
            hasOtherLiveRevision: true,
        )->isDiscardable);
    }

    /**
     * @param list<string> $transitions
     */
    private function resolve(
        RevisionStatus $status,
        ?RevisionStatus $previous = null,
        array $transitions = [],
        bool $hasOtherLiveRevision = false,
        bool $isItselfLive = false,
    ): RevisionActions {
        $revision = self::createStub(RevisionInterface::class);
        $revision->method('getStatus')->willReturn($status);

        if (null !== $previous) {
            $previousRevision = self::createStub(RevisionInterface::class);
            $previousRevision->method('getStatus')->willReturn($previous);
            $revision->method('getPreviousRevision')->willReturn($previousRevision);
        }

        $live = null;
        if ($isItselfLive) {
            $live = $revision;
        } elseif ($hasOtherLiveRevision) {
            $live = self::createStub(RevisionInterface::class);
        }

        $revisable = self::createStub(RevisableInterface::class);
        $revisable->method('getLiveRevision')->willReturn($live);
        $revision->method('getRevisable')->willReturn($revisable);

        $workflow = self::createStub(WorkflowInterface::class);
        $workflow->method('getEnabledTransitions')->willReturn(array_map(
            static fn (string $name): Transition => new Transition(
                $name,
                'in-review',
                'approved',
            ),
            $transitions,
        ));

        return new RevisionActionResolver($workflow)->resolve($revision);
    }
}
