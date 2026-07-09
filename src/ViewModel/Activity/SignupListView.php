<?php

declare(strict_types=1);

namespace App\ViewModel\Activity;

use App\Entity\Activity\Enums\AllocationMethod;
use App\Entity\Activity\ExternalSignup;
use App\Entity\Activity\SignupList;
use App\Entity\Activity\UserSignup;
use App\Entity\Application\Enums\Languages;
use DateTime;
use Symfony\Contracts\Translation\TranslatorInterface;

use function count;

/**
 * Read-model of a {@see SignupList} for the activity view page. Pre-computes the localised name, the sign-up window,
 * the subscriber count and when the viewer may see details also the subscriber rows, so the Twig stays declarative and
 * free of permission/sensitivity/`instanceof` logic.
 */
final readonly class SignupListView
{
    /**
     * @param ?DateTime   $drawAt     the announced moment of the upcoming lottery draw; only set for a conditional
     *                                draw that has not been performed yet (first-come-first-served needs no
     *                                member-facing announcement)
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
        public ?DateTime $drawAt,
        public bool $onlyGEWIS,
        public bool $displaySubscribedNumber,
        public bool $promoted,
        public bool $isOpen,
        public bool $isClosed,
        public bool $cancelled,
        public int $subscriberCount,
        public bool $canViewDetails,
        public bool $viewerHasSignup,
        public bool $hasSensitiveField,
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

        // Hide externals that have not confirmed their email yet from both the count and the rows. A confirmed sign-up
        // is exactly one whose verification moment is set (manually-added externals have it set immediately).
        $visibleSignups = [];
        foreach ($signupList->getSignUps() as $signup) {
            if (
                $signup instanceof ExternalSignup
                && null === $signup->getVerifiedAt()
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
            drawAt: AllocationMethod::ConditionalDraw === $signupList->getAllocationMethod()
                && !$signupList->isDrawLocked()
                    ? $signupList->getAutoDrawAt()
                    : null,
            onlyGEWIS: $signupList->getOnlyGEWIS(),
            displaySubscribedNumber: $signupList->getDisplaySubscribedNumber(),
            promoted: $signupList->isPromoted(),
            isOpen: $signupList->isOpen(),
            isClosed: $signupList->isClosed(),
            cancelled: $signupList->getActivity()->isCancelled(),
            subscriberCount: count($visibleSignups),
            canViewDetails: $canViewDetails,
            viewerHasSignup: $viewerHasSignup,
            hasSensitiveField: $signupList->hasSensitiveField(),
            fieldNames: $fieldNames,
            rows: $rows,
        );
    }
}
