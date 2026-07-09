<?php

declare(strict_types=1);

namespace App\Twig\Extensions;

use App\Entity\Activity\Activity;
use App\Entity\Application\Enums\Languages;
use App\Entity\Application\LocalisedText as LocalisedTextModel;
use Override;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class LocaliseTextExtension extends AbstractExtension
{
    public function __construct(
        private readonly TranslatorInterface $translator,
    ) {
    }

    /**
     * @return TwigFilter[]
     */
    #[Override]
    public function getFilters(): array
    {
        return [
            new TwigFilter(
                'localise_text',
                $this->localiseText(...),
            ),
        ];
    }

    /**
     * @return TwigFunction[]
     */
    #[Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'activity_title',
                $this->activityTitle(...),
            ),
        ];
    }

    public function localiseText(LocalisedTextModel $localisedText): string
    {
        return $localisedText->getText(Languages::current()) ?? '';
    }

    /**
     * The activity's localised display name, prefixed with a translated {@code [CANCELLED]} marker when the board has
     * cancelled it. Used everywhere the public/admin title of an activity is shown so a cancelled activity is
     * unmistakable at a glance.
     */
    public function activityTitle(Activity $activity): string
    {
        $name = $this->localiseText($activity->getName());

        if ($activity->isCancelled()) {
            return $this->translator->trans('[CANCELLED]') . ' ' . $name;
        }

        return $name;
    }
}
