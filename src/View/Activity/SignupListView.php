<?php

declare(strict_types=1);

namespace App\View\Activity;

use App\Entity\Activity\Enums\SignupFieldTypes;
use App\Entity\Activity\Signup;
use App\Entity\Activity\SignupField;
use App\Entity\Activity\SignupList;
use App\Entity\Activity\UserSignup;
use App\Entity\Application\Enums\Languages;
use DateTime;
use Locale;
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
        $language = 'nl' === Locale::getDefault()
            ? Languages::Dutch
            : Languages::English;

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
                        'value' => self::formatFieldValue(
                            $field,
                            $signup,
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
            onlyGEWIS: $signupList->getOnlyGEWIS(),
            displaySubscribedNumber: $signupList->getDisplaySubscribedNumber(),
            promoted: $signupList->isPromoted(),
            isOpen: $signupList->isOpen(),
            isClosed: $signupList->getCloseDate() < new DateTime('now'),
            subscriberCount: $signupList->getSignUps()->count(),
            canViewDetails: $canViewDetails,
            fieldNames: $fieldNames,
            rows: $rows,
        );
    }

    /**
     * Formats a sign-up field value by its type: 0 = text, 1 = yes/no (translated), 2 = number, 3 = option (localised).
     */
    private static function formatFieldValue(
        SignupField $field,
        Signup $signup,
        TranslatorInterface $translator,
        Languages $language,
    ): string {
        foreach ($signup->getFieldValues() as $fieldValue) {
            if ($fieldValue->getField()->getId() !== $field->getId()) {
                continue;
            }

            return match ($field->getType()) {
                SignupFieldTypes::YesNo => $translator->trans($fieldValue->getValue() ?? ''),
                SignupFieldTypes::Choice => $fieldValue->getOption()?->getValue()->getText($language) ?? '',
                default => $fieldValue->getValue() ?? '',
            };
        }

        return '';
    }
}
