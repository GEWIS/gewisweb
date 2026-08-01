<?php

declare(strict_types=1);

namespace App\Twig\Components\Concerns;

use Symfony\UX\LiveComponent\Attribute\LiveProp;

use function in_array;

/**
 * The page-size selection shared by the paginated overview components; the pagination partial renders the selector.
 * Read the size through {@see pageSize()}: the prop is client-writable, so it is clamped to the allowed steps.
 */
trait PageSizeTrait
{
    public const array PAGE_SIZES = [
        10,
        25,
        50,
        100,
    ];

    #[LiveProp(
        writable: true,
        url: true,
        onUpdated: 'onPageSizeUpdated',
    )]
    public int $pageSize = 10;

    public function onPageSizeUpdated(): void
    {
        $this->pageSize = $this->pageSize();
        $this->page = 1;
    }

    protected function pageSize(): int
    {
        return in_array(
            $this->pageSize,
            self::PAGE_SIZES,
            true,
        )
            ? $this->pageSize
            : 10;
    }
}
