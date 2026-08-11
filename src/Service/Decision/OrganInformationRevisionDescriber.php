<?php

declare(strict_types=1);

namespace App\Service\Decision;

use App\Entity\Application\RevisionInterface;
use App\Entity\Decision\OrganInformationRevision;
use App\Service\Application\AbstractRevisionDescriber;
use App\ViewModel\Application\Review\RevisionFieldKind;
use App\ViewModel\Application\Review\RevisionSection;
use Override;

use function assert;
use function Symfony\Component\Translation\t;

/**
 * What a body says about itself: the text on its page, how it is reached, the logo it is recognised by, the banner its
 * own page leads with, and where else it can be followed. The images are described so the board sees the one being
 * proposed beside the one it replaces, which is the part of approving a page that reading the text could never answer.
 */
final class OrganInformationRevisionDescriber extends AbstractRevisionDescriber
{
    #[Override]
    protected function revisionClass(): string
    {
        return OrganInformationRevision::class;
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
        assert($revision instanceof OrganInformationRevision);
        assert(null === $previous || $previous instanceof OrganInformationRevision);

        return [
            new RevisionSection(
                t('Page'),
                [
                    $this->localisedField(
                        t('Short description'),
                        $previous?->getShortDescription(),
                        $revision->getShortDescription(),
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
                        t('Email address'),
                        RevisionFieldKind::Text,
                        $previous?->getEmail(),
                        $revision->getEmail(),
                        $comparable,
                        ['width' => 'half'],
                    ),
                    $this->field(
                        t('Website'),
                        RevisionFieldKind::Text,
                        $previous?->getWebsite(),
                        $revision->getWebsite(),
                        $comparable,
                        ['width' => 'half'],
                    ),
                ],
            ),
            new RevisionSection(
                t('Social media'),
                $this->socialFields(
                    $previous?->getSocialLinks(),
                    $revision->getSocialLinks(),
                    $comparable,
                ),
            ),
            new RevisionSection(
                t('Images'),
                [
                    $this->field(
                        t('Logo'),
                        RevisionFieldKind::Image,
                        $previous?->getLogoPath(),
                        $revision->getLogoPath(),
                        $comparable,
                        [
                            'variant' => 'w320',
                            'class' => 'organ-logo',
                        ],
                        emptyLabel: t('No logo.'),
                    ),
                    $this->field(
                        t('Page banner'),
                        RevisionFieldKind::Image,
                        $previous?->getBannerPath(),
                        $revision->getBannerPath(),
                        $comparable,
                        [
                            'variant' => 'w640',
                            'class' => 'organ-banner',
                        ],
                        emptyLabel: t('No banner.'),
                    ),
                ],
            ),
        ];
    }
}
