<?php

declare(strict_types=1);

namespace App\View\Activity;

use App\Entity\Activity\SignupList;
use App\Entity\Activity\UserSignup;
use App\Entity\Application\Enums\Languages;
use DateTime;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Read-model of a {@see SignupList} for the activity view page. Pre-computes the localised name, the sign-up window,
 * the subscriber count and when the viewer may see details also the subscriber rows, so the Twig stays declarative and
 * free of permission/sensitivity/`instanceof` logic.
 */
final readonly class SignupListView
{
    /**
     * @param string[]    $fieldNames column headers (localised), one per sign-up field
     * @param SignupRow[] $rows       populated only when $canViewDetails
     */
    public function __construct(
        public string $name,
        public DateTime $openDate,
        public DateTime $closeDate,
        public bool $limitedCapacity,
        public ?int $capacity,
        public bool $onlyGEWIS,
        public bool $displaySubscribedNumber,
        public bool $promoted,
        public bool $isOpen,
        public bool $isClosed,
        public int $subscriberCount,
        public bool $canViewDetails,
        public array $fieldNames,
        public array $rows,
    ) {
    }

    public static function fromSignupList(
        SignupList $signupList,
        bool $canViewDetails,
        ?int $viewerLidnr,
        TranslatorInterface $translator,
    ): self {
        $language = Languages::current();

        $fields = $signupList->getFields()->toArray();
        $fieldNames = [];
        foreach ($fields as $field) {
            $fieldNames[] = $field->getName()->getText($language) ?? '';
        }

        $rows = [];
        if ($canViewDetails) {
            $position = 1;
            foreach ($signupList->getSignUps() as $signup) {
                $isOwn = $signup instanceof UserSignup
                    && null !== $viewerLidnr
                    && $viewerLidnr === $signup->getUser()->getLidnr();

                $cells = [];
                foreach ($fields as $field) {
                    if (
                        $field->isSensitive()
                        && !$isOwn
                    ) {
                        $cells[] = [
                            'hidden' => true,
                            'value' => '',
                        ];

                        continue;
                    }

                    $cells[] = [
                        'hidden' => false,
                        'value' => $signup->displayValueForField(
                            $field,
                            $translator,
                            $language,
                        ),
                    ];
                }

                $rows[] = new SignupRow(
                    $position++,
                    $signup->getFullName(),
                    $cells,
                );
            }
        }

        return new self(
            name: $signupList->getName()->getText($language) ?? '',
            openDate: $signupList->getOpenDate(),
            closeDate: $signupList->getCloseDate(),
            limitedCapacity: $signupList->getLimitedCapacity(),
            capacity: $signupList->getCapacity(),
            onlyGEWIS: $signupList->getOnlyGEWIS(),
            displaySubscribedNumber: $signupList->getDisplaySubscribedNumber(),
            promoted: $signupList->isPromoted(),
            isOpen: $signupList->isOpen(),
            isClosed: $signupList->isClosed(),
            subscriberCount: $signupList->getSignUps()->count(),
            canViewDetails: $canViewDetails,
            fieldNames: $fieldNames,
            rows: $rows,
        );
    }
}
