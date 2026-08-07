<?php

declare(strict_types=1);

namespace App\Service\Career;

use App\Entity\Application\RevisionInterface;
use App\Entity\Career\CompanyRevision;
use App\Service\Application\AbstractRevisionDescriber;
use App\ViewModel\Application\Review\RevisionFieldKind;
use App\ViewModel\Application\Review\RevisionSection;
use Override;

use function assert;
use function Symfony\Component\Translation\t;

/**
 * What a company says about itself: the profile it publishes, how to reach it and the logo it is shown by.
 */
final class CompanyRevisionDescriber extends AbstractRevisionDescriber
{
    #[Override]
    protected function revisionClass(): string
    {
        return CompanyRevision::class;
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
        assert($revision instanceof CompanyRevision);
        assert(null === $previous || $previous instanceof CompanyRevision);

        return [
            new RevisionSection(
                t('Profile'),
                [
                    $this->localisedField(
                        t('Slogan'),
                        $previous?->getSlogan(),
                        $revision->getSlogan(),
                        $comparable,
                    ),
                    $this->localisedField(
                        t('Website'),
                        $previous?->getWebsite(),
                        $revision->getWebsite(),
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
                    $this->field(
                        t('Address'),
                        RevisionFieldKind::Text,
                        $previous?->getContactAddress(),
                        $revision->getContactAddress(),
                        $comparable,
                    ),
                ],
            ),
            new RevisionSection(
                t('Logos'),
                [
                    $this->field(
                        t('Square logo'),
                        RevisionFieldKind::Image,
                        $previous?->getSquareLogo(),
                        $revision->getSquareLogo(),
                        $comparable,
                        [
                            'variant' => 'w320',
                            'class' => 'career-logo-lg',
                        ],
                        emptyLabel: t('No logo.'),
                    ),
                    $this->field(
                        t('Banner logo'),
                        RevisionFieldKind::Image,
                        $previous?->getBannerLogo(),
                        $revision->getBannerLogo(),
                        $comparable,
                        [
                            'variant' => 'w640',
                            'class' => 'career-logo-plate',
                        ],
                        emptyLabel: t('No logo.'),
                    ),
                ],
            ),
        ];
    }
}
