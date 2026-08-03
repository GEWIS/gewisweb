<?php

declare(strict_types=1);

namespace App\ViewModel\Application\Review;

use App\Entity\Application\Enums\Languages;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * What one field held before this revision and what it holds now, raw. Nothing here is rendered or translated yet,
 * which is what lets the same description serve the reviewer's screen and the author's.
 *
 * A field that exists once per language carries one of these per language; anything else carries a single value with
 * no language at all. Whether the old value means anything is {@see RevisionField::$comparable}, since a first
 * revision has nothing behind it and every old value is null for that reason alone.
 */
final readonly class RevisionFieldValue
{
    /**
     * @param string|bool|TranslatableInterface|RevisionDateRange|list<RevisionTag>|list<RevisionFlag>|null $old
     * @param string|bool|TranslatableInterface|RevisionDateRange|list<RevisionTag>|list<RevisionFlag>|null $new
     */
    public function __construct(
        public string|bool|TranslatableInterface|RevisionDateRange|array|null $old,
        public string|bool|TranslatableInterface|RevisionDateRange|array|null $new,
        public ?Languages $language = null,
    ) {
    }

    public function isChanged(): bool
    {
        if (
            $this->old instanceof RevisionDateRange
            && $this->new instanceof RevisionDateRange
        ) {
            return !$this->old->equals($this->new);
        }

        // A translatable value is usually built fresh on every call, so identity says nothing about it. An enum that
        // is translatable is its own singleton and falls through to the comparison below, which is what it wants.
        if (
            $this->old instanceof TranslatableMessage
            && $this->new instanceof TranslatableMessage
        ) {
            return $this->old->getMessage() !== $this->new->getMessage()
                || $this->old->getParameters() !== $this->new->getParameters();
        }

        return $this->old !== $this->new;
    }
}
