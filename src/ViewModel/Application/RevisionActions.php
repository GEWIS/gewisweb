<?php

declare(strict_types=1);

namespace App\ViewModel\Application;

/**
 * What can be done with one revision right now: which workflow transitions are open, whether resubmitting it has to
 * carry a response, and whether the draft may simply be thrown away. Read once per request by
 * {@see \App\Service\Application\RevisionActionResolver} so the review screens and the decision form agree.
 */
final readonly class RevisionActions
{
    /**
     * @param list<string> $enabledTransitions
     */
    public function __construct(
        public array $enabledTransitions,
        public bool $isResubmission,
        public bool $isDiscardable,
    ) {
    }

    /**
     * @return array{enabled_transitions: list<string>, resubmission: bool}
     */
    public function toFormOptions(): array
    {
        return [
            'enabled_transitions' => $this->enabledTransitions,
            'resubmission' => $this->isResubmission,
        ];
    }
}
