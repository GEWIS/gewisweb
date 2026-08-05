<?php

declare(strict_types=1);

namespace App\Service\Activity;

use App\Entity\Activity\ActivityRevision;
use App\Entity\Application\RevisionInterface;
use App\Service\Application\AbstractRevisionDescriber;
use App\ViewModel\Application\Review\RevisionDateRange;
use App\ViewModel\Application\Review\RevisionFieldKind;
use App\ViewModel\Application\Review\RevisionFlag;
use App\ViewModel\Application\Review\RevisionSection;
use App\ViewModel\Application\Review\RevisionTag;
use Override;

use function assert;
use function Symfony\Component\Translation\t;

/**
 * What an activity is, when and where it happens and what it asks the association for. Its sign-up lists are not here:
 * they are a structure of their own, matched by lineage rather than compared field by field, and the activity review
 * screen renders them itself.
 */
final class ActivityRevisionDescriber extends AbstractRevisionDescriber
{
    #[Override]
    protected function revisionClass(): string
    {
        return ActivityRevision::class;
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
        assert($revision instanceof ActivityRevision);
        assert(null === $previous || $previous instanceof ActivityRevision);

        return [
            new RevisionSection(
                t('General information'),
                [
                    $this->field(
                        t('Organising organ'),
                        RevisionFieldKind::Reference,
                        $previous?->getOrgan()?->getAbbr(),
                        $revision->getOrgan()?->getAbbr(),
                        $comparable,
                        ['width' => 'half'],
                        emptyLabel: t('None'),
                    ),
                    $this->field(
                        t('Organising company'),
                        RevisionFieldKind::Reference,
                        $previous?->getCompany()?->getName(),
                        $revision->getCompany()?->getName(),
                        $comparable,
                        ['width' => 'half'],
                        emptyLabel: t('None'),
                    ),
                    $this->field(
                        t('Date and time'),
                        RevisionFieldKind::DateRange,
                        null === $previous ? null : new RevisionDateRange(
                            $previous->getBeginTime(),
                            $previous->getEndTime(),
                        ),
                        new RevisionDateRange(
                            $revision->getBeginTime(),
                            $revision->getEndTime(),
                        ),
                        $comparable,
                        ['format' => 'activity'],
                    ),
                    $this->field(
                        t('Category'),
                        RevisionFieldKind::Badge,
                        $previous?->getCategory(),
                        $revision->getCategory(),
                        $comparable,
                        ['badgeClass' => 'badge-gewis-primary'],
                    ),
                    $this->field(
                        t('Labels'),
                        RevisionFieldKind::Tags,
                        $this->labels($previous),
                        $this->labels($revision),
                        $comparable,
                        emptyLabel: t('No labels.'),
                    ),
                    $this->field(
                        t('Facilities'),
                        RevisionFieldKind::Flag,
                        null,
                        [
                            new RevisionFlag(
                                t('GEFLITST'),
                                $previous?->getRequireGEFLITST(),
                                $revision->getRequireGEFLITST(),
                            ),
                            new RevisionFlag(
                                t('Zettle'),
                                $previous?->getRequireZettle(),
                                $revision->getRequireZettle(),
                            ),
                        ],
                        $comparable,
                        emptyLabel: t('None'),
                    ),
                ],
            ),
            new RevisionSection(
                t('Details'),
                [
                    $this->localisedField(
                        t('Name'),
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
                        t('Costs'),
                        $previous?->getCosts(),
                        $revision->getCosts(),
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
        ];
    }

    /**
     * @return list<RevisionTag>
     */
    private function labels(?ActivityRevision $revision): array
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
