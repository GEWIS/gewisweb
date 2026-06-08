<?php

declare(strict_types=1);

namespace App\ViewModel\Activity;

use App\Entity\Activity\ExternalSignup;
use App\Entity\Activity\SignupList;
use App\Entity\Activity\UserSignup;
use App\Entity\Application\Enums\Languages;
use DateTime;
use Symfony\Contracts\Translation\TranslatorInterface;

use function count;
use function in_array;

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
        public int $listId,
        public int $activityId,
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
        public bool $viewerHasSignup,
        public bool $hasSensitiveField,
        public array $fieldNames,
        public array $rows,
    ) {
    }

    /**
     * @param int[] $pendingExternalSignupIds external sign-ups still awaiting e-mail verification; excluded from the
     *                                        public rows and count until confirmed
     */
    public static function fromSignupList(
        SignupList $signupList,
        bool $canViewDetails,
        ?int $viewerLidnr,
        TranslatorInterface $translator,
        array $pendingExternalSignupIds = [],
    ): self {
        $language = Languages::current();

        $fields = $signupList->getFields()->toArray();
        $fieldNames = [];
        $hasSensitiveField = false;
        foreach ($fields as $field) {
            $fieldNames[] = $field->getName()->getText($language) ?? '';
            if (!$field->isSensitive()) {
                continue;
            }

            $hasSensitiveField = true;
        }

        // Hide externals that have not confirmed their e-mail yet from both the count and the rows.
        $visibleSignups = [];
        foreach ($signupList->getSignUps() as $signup) {
            if (
                $signup instanceof ExternalSignup
                && in_array(
                    $signup->getId(),
                    $pendingExternalSignupIds,
                    true,
                )
            ) {
                continue;
            }

            $visibleSignups[] = $signup;
        }

        $rows = [];
        $viewerHasSignup = false;
        if ($canViewDetails) {
            $position = 1;
            foreach ($visibleSignups as $signup) {
                $isOwn = $signup instanceof UserSignup
                    && null !== $viewerLidnr
                    && $viewerLidnr === $signup->getUser()->getLidnr();

                if ($isOwn) {
                    $viewerHasSignup = true;
                }

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
            listId: (int) $signupList->getId(),
            activityId: (int) $signupList->getActivity()->getId(),
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
            subscriberCount: count($visibleSignups),
            canViewDetails: $canViewDetails,
            viewerHasSignup: $viewerHasSignup,
            hasSensitiveField: $hasSensitiveField,
            fieldNames: $fieldNames,
            rows: $rows,
        );
    }
}
