<?php

declare(strict_types=1);

namespace App\ViewModel\Application\Review;

use App\Entity\Application\Enums\Languages;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * One thing a revision says about itself: what it is called, how it should be read, and what it holds now against what
 * it held before.
 *
 * The label stays translatable rather than translated, so the extractor still finds it and the reader's own locale
 * still applies. {@see $options} carries the hints a renderer needs and a describer knows, such as which image variant
 * to serve or how wide the field sits.
 */
final readonly class RevisionField
{
    /**
     * @param non-empty-list<RevisionFieldValue> $values
     * @param array<string, string>              $options
     * @param TranslatableInterface|null         $emptyLabel what to say when the field holds nothing at all
     */
    public function __construct(
        public TranslatableInterface $label,
        public RevisionFieldKind $kind,
        public array $values,
        public bool $comparable = false,
        public array $options = [],
        public RevisionAudience $audience = RevisionAudience::Everyone,
        public ?TranslatableInterface $emptyLabel = null,
    ) {
    }

    /**
     * Whether this field is written once per language, and therefore belongs in the language columns rather than in a
     * row of its own.
     */
    public function isLocalised(): bool
    {
        return null !== $this->values[0]->language;
    }

    /**
     * The single value of a field that is not localised.
     */
    public function value(): RevisionFieldValue
    {
        return $this->values[0];
    }

    public function dutch(): ?RevisionFieldValue
    {
        return $this->valueFor(Languages::Dutch);
    }

    public function english(): ?RevisionFieldValue
    {
        return $this->valueFor(Languages::English);
    }

    private function valueFor(Languages $language): ?RevisionFieldValue
    {
        foreach ($this->values as $value) {
            if ($language === $value->language) {
                return $value;
            }
        }

        return null;
    }

    public function option(string $name): ?string
    {
        return $this->options[$name] ?? null;
    }
}
