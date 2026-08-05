<?php

declare(strict_types=1);

namespace App\Service\Career;

use App\Entity\Application\RevisionInterface;
use App\Entity\Career\VacancyRevision;
use App\Service\Application\AbstractRevisionDescriber;
use App\ViewModel\Application\Review\RevisionAudience;
use App\ViewModel\Application\Review\RevisionDateRange;
use App\ViewModel\Application\Review\RevisionFieldKind;
use App\ViewModel\Application\Review\RevisionSection;
use App\ViewModel\Application\Review\RevisionTag;
use Override;

use function assert;
use function Symfony\Component\Translation\t;

/**
 * What a vacancy offers, when it runs and who to talk to about it. Which company it belongs to only tells a reviewer
 * something: a representative is already inside their own company when they read this.
 */
final class VacancyRevisionDescriber extends AbstractRevisionDescriber
{
    #[Override]
    protected function revisionClass(): string
    {
        return VacancyRevision::class;
    }

    /**
     * @inheritDoc
     */
    #[Override]
    protected function sections(
        RevisionInterface $revision,
        ?RevisionInterface $previous,
        bool $comparable,
    ): array {
        assert($revision instanceof VacancyRevision);
        assert(null === $previous || $previous instanceof VacancyRevision);

        return [
            new RevisionSection(
                t('General information'),
                [
                    $this->field(
                        t('Company'),
                        RevisionFieldKind::Reference,
                        $previous?->getVacancy()->getCompany()->getName(),
                        $revision->getVacancy()->getCompany()->getName(),
                        $comparable,
                        ['width' => 'third'],
                        RevisionAudience::ReviewerOnly,
                    ),
                    $this->field(
                        t('Category'),
                        RevisionFieldKind::Badge,
                        $previous?->getCategory()->label(),
                        $revision->getCategory()->label(),
                        $comparable,
                        [
                            'width' => 'third',
                            'badgeClass' => $revision->getCategory()->badgeClass(),
                        ],
                    ),
                    $this->field(
                        t('Posting window'),
                        RevisionFieldKind::DateRange,
                        null === $previous ? null : new RevisionDateRange(
                            $previous->getStartDate(),
                            $previous->getEndDate(),
                        ),
                        new RevisionDateRange(
                            $revision->getStartDate(),
                            $revision->getEndDate(),
                        ),
                        $comparable,
                        ['width' => 'third'],
                    ),
                    $this->field(
                        t('Labels'),
                        RevisionFieldKind::Tags,
                        $this->labels($previous),
                        $this->labels($revision),
                        $comparable,
                        emptyLabel: t('No labels.'),
                    ),
                ],
            ),
            new RevisionSection(
                t('Details'),
                [
                    $this->localisedField(
                        t('Title'),
                        $previous?->getName(),
                        $revision->getName(),
                        $comparable,
                    ),
                    $this->localisedField(
                        t('Location'),
                        $previous?->getLocation(),
                        $revision->getLocation(),
                        $comparable,
                    ),
                    $this->localisedField(
                        t('Website'),
                        $previous?->getWebsite(),
                        $revision->getWebsite(),
                        $comparable,
                    ),
                    $this->localisedField(
                        t('Attachment link'),
                        $previous?->getAttachment(),
                        $revision->getAttachment(),
                        $comparable,
                    ),
                    $this->localisedField(
                        t('Description'),
                        $previous?->getDescription(),
                        $revision->getDescription(),
                        $comparable,
                        RevisionFieldKind::LongText,
                    ),
                ],
            ),
            new RevisionSection(
                t('Contact details'),
                [
                    $this->field(
                        t('Contact name'),
                        RevisionFieldKind::Text,
                        $previous?->getContactName(),
                        $revision->getContactName(),
                        $comparable,
                    ),
                    $this->field(
                        t('Contact email address'),
                        RevisionFieldKind::Text,
                        $previous?->getContactEmail(),
                        $revision->getContactEmail(),
                        $comparable,
                    ),
                    $this->field(
                        t('Contact phone number'),
                        RevisionFieldKind::Text,
                        $previous?->getContactPhone(),
                        $revision->getContactPhone(),
                        $comparable,
                    ),
                ],
            ),
        ];
    }

    /**
     * @return list<RevisionTag>
     */
    private function labels(?VacancyRevision $revision): array
    {
        $tags = [];

        foreach ($revision?->getLabels() ?? [] as $label) {
            $tags[] = new RevisionTag(
                $label->getId(),
                $label->getName(),
            );
        }

        return $tags;
    }
}
