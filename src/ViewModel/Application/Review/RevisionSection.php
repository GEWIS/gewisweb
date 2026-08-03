<?php

declare(strict_types=1);

namespace App\ViewModel\Application\Review;

use Symfony\Contracts\Translation\TranslatableInterface;

use function array_filter;
use function array_values;

/**
 * One panel of a comparison. Fields that are written per language are laid out in the language columns underneath
 * whatever the section says once, which is the order every review screen already used by hand.
 */
final readonly class RevisionSection
{
    /**
     * @param list<RevisionField> $fields
     */
    public function __construct(
        public TranslatableInterface $heading,
        public array $fields,
        public RevisionAudience $audience = RevisionAudience::Everyone,
    ) {
    }

    /**
     * @return list<RevisionField>
     */
    public function localisedFields(): array
    {
        return array_values(array_filter(
            $this->fields,
            static fn (RevisionField $field): bool => $field->isLocalised(),
        ));
    }

    /**
     * @return list<RevisionField>
     */
    public function plainFields(): array
    {
        return array_values(array_filter(
            $this->fields,
            static fn (RevisionField $field): bool => !$field->isLocalised(),
        ));
    }

    /**
     * The same section with only the fields {@see $audience} is allowed to see, or null when that leaves nothing.
     */
    public function forAudience(RevisionAudience $audience): ?self
    {
        if (!$audience->canSee($this->audience)) {
            return null;
        }

        $fields = array_values(array_filter(
            $this->fields,
            static fn (RevisionField $field): bool => $audience->canSee($field->audience),
        ));

        if ([] === $fields) {
            return null;
        }

        return new self(
            $this->heading,
            $fields,
            $this->audience,
        );
    }
}
