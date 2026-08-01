<?php

declare(strict_types=1);

namespace App\Service\Decision;

use App\Entity\Decision\Enums\MeetingTypes;

use function trim;

/**
 * What {@see DecisionSearchQueryParser} read from a decision search prompt.
 */
final readonly class DecisionSearchQuery
{
    /**
     * @param list<string>  $includeTerms words and phrases the decision text must all contain
     * @param list<string>  $excludeTerms words and phrases the decision text must not contain
     * @param ?MeetingTypes $type         restricts text matches to one meeting type
     * @param string        $remainder    the prompt without operators, checked for references like "GMM 214.3.1"
     */
    public function __construct(
        public array $includeTerms,
        public array $excludeTerms,
        public ?MeetingTypes $type,
        public string $remainder,
    ) {
    }

    public function isEmpty(): bool
    {
        return [] === $this->includeTerms
            && [] === $this->excludeTerms
            && null === $this->type
            && '' === trim($this->remainder);
    }
}
