<?php

declare(strict_types=1);

namespace App\Twig\Extensions;

use Override;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class PageTitleExtension extends AbstractExtension
{
    private string $separator = ' - ';

    /**
     * @return TwigFilter[]
     */
    #[Override]
    public function getFilters(): array
    {
        return [
            new TwigFilter(
                'page_title',
                $this->formatTitle(...),
            ),
        ];
    }

    public function formatTitle(string $title): string
    {
        return $title . $this->separator;
    }
}
