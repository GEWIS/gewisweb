<?php

declare(strict_types=1);

namespace App\ViewModel\Application\Review;

/**
 * Everything one revision changed, as the sections a review screen renders. Built by the domain's describer and read
 * by one template, which is what keeps the author's screen showing the same fields as the reviewer's.
 */
final readonly class RevisionComparison
{
    /**
     * @param list<RevisionSection> $sections
     */
    public function __construct(
        public array $sections,
    ) {
    }

    /**
     * @return list<RevisionSection>
     */
    public function sectionsFor(RevisionAudience $audience): array
    {
        $sections = [];

        foreach ($this->sections as $section) {
            $visible = $section->forAudience($audience);

            if (null === $visible) {
                continue;
            }

            $sections[] = $visible;
        }

        return $sections;
    }
}
