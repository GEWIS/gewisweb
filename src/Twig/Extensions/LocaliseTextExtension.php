<?php

declare(strict_types=1);

namespace App\Twig\Extensions;

use App\Entity\Application\Enums\Languages;
use App\Entity\Application\LocalisedText as LocalisedTextModel;
use Locale;
use Override;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class LocaliseTextExtension extends AbstractExtension
{
    public function __construct()
    {
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

    public function localiseText(LocalisedTextModel $localisedText): string
    {
        return $localisedText->getText(Languages::fromLangParam(Locale::getDefault())) ?? '';
    }
}
