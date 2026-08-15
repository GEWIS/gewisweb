<?php

declare(strict_types=1);

namespace App\Entity\Activity\Enums;

use Override;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Where an activity proposal stands in the option calendar.
 *
 * The string values are also the place names of the `activity_proposal` Symfony Workflow
 * {@see config/packages/workflow.yaml}. Keep the two in lockstep.
 *
 *   Submitted -> { Scheduled -> { Cleared | Lapsed } | Declined } -> (Withdrawn) -> (reopen -> Submitted)
 */
enum ProposalStatus: string implements TranslatableInterface
{
    /** Waiting for the board to pick one of its dates. The initial place. */
    case Submitted = 'submitted';

    /** The board reserved one of the dates; the activity itself still has to be built and budgeted. */
    case Scheduled = 'scheduled';

    /** The financial side is settled, either by an approved budget or because the activity costs nothing. */
    case Cleared = 'cleared';

    /** The board turned the proposal down; none of its dates are held. */
    case Declined = 'declined';

    /** The body took the proposal back; none of its dates are held. */
    case Withdrawn = 'withdrawn';

    /** The reserved date came too close without the financial side being settled, so it was released. */
    case Lapsed = 'lapsed';

    /**
     * Whether a proposal in this state holds its date against everybody else.
     */
    public function holdsADate(): bool
    {
        return match ($this) {
            self::Scheduled,
            self::Cleared => true,
            default => false,
        };
    }

    /**
     * Whether a proposal in this state counts against its body's allowance for the period. A proposal the board turned
     * down, or that was taken back, does not burn a slot for the rest of the quartile.
     */
    public function countsTowardsAllowance(): bool
    {
        return match ($this) {
            self::Submitted,
            self::Scheduled,
            self::Cleared => true,
            default => false,
        };
    }

    /**
     * Whether the body may still change what it asked for. Once a date is held, changing the dates would mean holding
     * a date nobody approved, so the way to change it is to withdraw and propose again.
     */
    public function isEditableByAuthor(): bool
    {
        return self::Submitted === $this;
    }

    /**
     * The states that make sense as a filter in the board's decision queue.
     *
     * @return list<self>
     */
    public static function decidableCases(): array
    {
        return [
            self::Submitted,
            self::Scheduled,
            self::Cleared,
        ];
    }

    /**
     * The states {@see self::countsTowardsAllowance()} answers true for, for the queries that have to say the same
     * thing in DQL.
     *
     * @return list<self>
     */
    public static function countingTowardsAllowance(): array
    {
        return [
            self::Submitted,
            self::Scheduled,
            self::Cleared,
        ];
    }

    /**
     * The states {@see self::holdsADate()} answers true for.
     *
     * @return list<self>
     */
    public static function holdingADate(): array
    {
        return [
            self::Scheduled,
            self::Cleared,
        ];
    }

    /**
     * The `.badge-*` modifier this state's badge is drawn with, so a status reads the same wherever it is shown.
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::Submitted => 'badge-info',
            self::Scheduled => 'badge-primary',
            self::Cleared => 'badge-success',
            self::Declined => 'badge-danger',
            self::Withdrawn,
            self::Lapsed => 'badge-secondary',
        };
    }

    #[Override]
    public function trans(
        TranslatorInterface $translator,
        ?string $locale = null,
    ): string {
        return match ($this) {
            self::Submitted => $translator->trans(
                'Awaiting a decision',
                locale: $locale,
            ),
            self::Scheduled => $translator->trans(
                'Date reserved',
                locale: $locale,
            ),
            self::Cleared => $translator->trans(
                'Ready to go',
                locale: $locale,
            ),
            self::Declined => $translator->trans(
                'Declined',
                locale: $locale,
            ),
            self::Withdrawn => $translator->trans(
                'Withdrawn',
                locale: $locale,
            ),
            self::Lapsed => $translator->trans(
                'Lapsed',
                locale: $locale,
            ),
        };
    }
}
