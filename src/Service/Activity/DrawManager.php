<?php

declare(strict_types=1);

namespace App\Service\Activity;

use App\Entity\Activity\Enums\AllocationMethod;
use App\Entity\Activity\ExternalSignup;
use App\Entity\Activity\Signup;
use App\Entity\Activity\SignupList;
use App\Entity\Decision\Member;
use App\Util\Activity\SignupAdminWindow;
use DateTime;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Random\Randomizer;

use function usort;

/**
 * Performs the one-shot admission draw on a limited-capacity sign-up list: admit the (optionally shuffled) confirmed
 * sign-ups up to capacity, waitlist the rest, and lock the draw. Shared by the board's manual draw
 * ({@see \App\Twig\Components\Activity\Admin\SignupOverview}) and the automated deadline draw
 * ({@see \App\Command\Activity\RunDueDrawsCommand}) so the guard rules, the deadline snapshot (only who was a
 * confirmed subscriber at the announced draw moment joins the lottery; later arrivals get the first-come-first-served
 * fill) and the locking transaction never diverge.
 *
 * Authorisation (board role, list ownership) stays with the callers; this service trusts what it is given.
 */
final readonly class DrawManager
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Board-run draw. Allowed once the list has closed, or (as a fallback for a missed automated draw) once the
     * list's own automated draw moment has passed; a board member can never pre-empt the announced moment. The
     * expected method guards against a stale button acting on a since-reconfigured list.
     *
     * Returns whether a draw was actually performed.
     */
    public function drawManually(
        SignupList $list,
        AllocationMethod $method,
        Member $drawnBy,
    ): bool {
        return $this->runDraw(
            $list,
            $method,
            $drawnBy,
            requireDue: false,
        );
    }

    /**
     * Scheduled draw at the list's own automated draw moment ({@see SignupList::getAutoDrawAt()}); the list need not
     * have closed, because {@see \App\Entity\Activity\Enums\DrawCutoffRule::IfFullBefore} and
     * {@see \App\Entity\Activity\Enums\DrawCutoffRule::AfterDurationOpen} legitimately fire while sign-up is still
     * open. A null drawnBy marks the draw as automated.
     *
     * Returns whether a draw was actually performed.
     */
    public function drawAutomatically(SignupList $list): bool
    {
        if ($list->getAllocationMethod()->isManual()) {
            return false;
        }

        return $this->runDraw(
            $list,
            $list->getAllocationMethod(),
            null,
            requireDue: true,
        );
    }

    private function runDraw(
        SignupList $list,
        AllocationMethod $method,
        ?Member $drawnBy,
        bool $requireDue,
    ): bool {
        $performed = false;

        // Serialise concurrent draws of the same list (a double-click, two tabs, or the manual draw racing the
        // automated one): a pessimistic write lock makes the second draw block until the first commits; refresh()
        // then re-reads the now-locked row so the canDraw() recheck sees the freshly set drawnAt and returns early:
        // a lottery is never re-run and its result never changes.
        $this->entityManager->wrapInTransaction(
            function () use ($list, $method, $drawnBy, $requireDue, &$performed): void {
                $this->entityManager->lock(
                    $list,
                    LockMode::PESSIMISTIC_WRITE,
                );
                $this->entityManager->refresh($list);

                if (
                    !$this->canDraw(
                        $list,
                        $method,
                        $requireDue,
                    )
                ) {
                    return;
                }

                // The pool is what existed at the *announced* draw moment, not at execution time: a late draw
                // (scheduler downtime, or the board's manual fallback) must not let sign-ups or email confirmations
                // from after that moment join the lottery. Latecomers come after the pool in the order they became
                // subscribers; the capacity counter in applyDraw() then continues over them, which reproduces
                // exactly the post-lock first-come-first-served fill an on-time draw would have produced
                // ({@see SignupManager::initialDrawnState()}).
                $this->applyDraw(
                    $list,
                    $this->signupsInDrawOrder(
                        $list,
                        $method,
                    ),
                    $drawnBy,
                );
                $performed = true;
            },
        );

        return $performed;
    }

    /**
     * Whether the given draw may be run on a list now: it is limited with a real capacity, uses that draw method, has
     * not been drawn yet, and we are within the admission window. An automated draw additionally requires the list's
     * own draw moment to have passed; a manual draw requires the list to have closed or that same moment to have
     * passed (the fallback for a missed automated draw). The capacity guard is essential: without it a capacity-less
     * limited list would admit zero and lock irreversibly.
     */
    private function canDraw(
        SignupList $list,
        AllocationMethod $method,
        bool $requireDue,
    ): bool {
        $allowed = $list->getLimitedCapacity()
            && null !== $list->getCapacity()
            && $list->getCapacity() >= 1
            && $list->getAllocationMethod() === $method
            && !$list->isDrawLocked()
            && !$list->getActivity()->isFrozen()
            && SignupAdminWindow::canChangeAdmission($list->getActivity()->getEndTime());

        if (!$allowed) {
            return false;
        }

        return $requireDue
            ? $list->isAutoDrawDue()
            : ($list->isClosed() || $list->isAutoDrawDue());
    }

    /**
     * Admit the first capacity of the (pre-ordered) sign-ups, waitlist the rest (clearing their attendance), then
     * lock the draw with an audit stamp. The draw is a one-shot event and cannot be re-run; later adjustments are
     * manual ({@see \App\Twig\Components\Activity\Admin\SignupOverview::toggleAdmission()}).
     *
     * @param Signup[] $orderedSignups the cutoff pool (shuffled for a lottery) followed by the latecomers in
     *                                 subscription order
     */
    private function applyDraw(
        SignupList $list,
        array $orderedSignups,
        ?Member $drawnBy,
    ): void {
        $capacity = $list->getCapacity() ?? 0;
        $position = 0;
        foreach ($orderedSignups as $signup) {
            $admitted = $position < $capacity;
            $signup->setDrawn($admitted);
            if (!$admitted) {
                $signup->setPresent(false);
            }

            ++$position;
        }

        $list->setDrawnAt(new DateTime());
        $list->setDrawnBy($drawnBy);

        $this->entityManager->flush();
    }

    /**
     * A list's confirmed sign-ups in admission order: first the cutoff pool (everyone who was a subscriber at the
     * announced draw moment) in id order (the on-time draw input), shuffled when the method is a lottery; then the
     * latecomers, sorted by when they became subscribers with id as tie-break (sign-ups created in the same second
     * must keep their arrival order). An external still awaiting email verification is not a real subscriber, so it
     * is skipped: it must never be drawn nor take up a capacity slot. Read inside the locking transaction, so it
     * always sees fresh data. The cutoff comparison is inclusive: all values are second-precision DATETIMEs, so a
     * sign-up in the same second as the cutoff made it. Lists without an own draw moment (manual methods, legacy
     * conditional-draw rows without a cutoff rule) are only drawable manually once closed, so their close is the
     * announced moment.
     *
     * @return list<Signup>
     */
    private function signupsInDrawOrder(
        SignupList $list,
        AllocationMethod $method,
    ): array {
        $cutoff = $list->getAutoDrawAt() ?? $list->getCloseDate();

        $pool = [];
        $late = [];
        foreach ($list->getSignUps() as $signup) {
            $subscribedAt = $this->subscribedAt($signup);
            if (null === $subscribedAt) {
                continue;
            }

            if ($subscribedAt <= $cutoff) {
                $pool[] = $signup;
                continue;
            }

            $late[] = [
                $subscribedAt,
                $signup,
            ];
        }

        if (AllocationMethod::ConditionalDraw === $method) {
            $pool = new Randomizer()->shuffleArray($pool);
        }

        usort(
            $late,
            static function (array $a, array $b): int {
                $order = $a[0] <=> $b[0];
                if (0 !== $order) {
                    return $order;
                }

                return $a[1]->getId() <=> $b[1]->getId();
            },
        );
        foreach ($late as $entry) {
            $pool[] = $entry[1];
        }

        return $pool;
    }

    /**
     * The moment a sign-up became a real subscriber (a member: sign-up creation; an external: email confirmation or
     * organiser add), or null for an external still awaiting its double opt-in.
     */
    private function subscribedAt(Signup $signup): ?DateTime
    {
        return $signup instanceof ExternalSignup
            ? $signup->getVerifiedAt()
            : $signup->getCreatedAt();
    }
}
