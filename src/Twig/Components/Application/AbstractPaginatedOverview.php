<?php

declare(strict_types=1);

namespace App\Twig\Components\Application;

use App\Twig\Components\Concerns\PageSizeTrait;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

use function ceil;
use function iterator_to_array;
use function max;
use function min;

/**
 * The paging every administrative overview does the same way: a page number, a clamped page size, the totals the
 * pagination partial renders, and one query per request.
 *
 * Subclasses answer {@see self::createPaginator()} with their own filtered query and keep their own filter props.
 * `#[AsLiveComponent]` and `#[IsGranted]` stay on the concrete component: the factory registers by attribute on the
 * class it finds, and each overview is gated differently.
 *
 * A subclass must bind the type it pages over, or static analysis rejects its `createPaginator()` return type against
 * this one:
 *
 *     &#64;extends AbstractPaginatedOverview&lt;Company&gt;
 *
 * @template T of object
 */
abstract class AbstractPaginatedOverview
{
    use DefaultActionTrait;
    use PageSizeTrait;

    #[LiveProp(writable: true)]
    public int $page = 1;

    /** @var Paginator<T>|null */
    private ?Paginator $paginator = null;

    /** The page {@see self::$paginator} was built for, so a page that changes mid-action does not reuse it. */
    private ?int $paginatorPage = null;

    /**
     * @return Paginator<T>
     */
    abstract protected function createPaginator(
        int $page,
        int $pageSize,
    ): Paginator;

    /**
     * @return list<T>
     */
    public function getRows(): array
    {
        return iterator_to_array(
            $this->paginator()->getIterator(),
            false,
        );
    }

    public function getTotalCount(): int
    {
        return $this->paginator()->count();
    }

    public function getTotalPages(): int
    {
        return max(
            1,
            (int) ceil($this->getTotalCount() / $this->pageSize()),
        );
    }

    #[LiveAction]
    public function gotoPage(#[LiveArg]
    int $page,): void
    {
        // Assign before clamping: working out the last page runs the query, and it has to run for the page that was
        // asked for rather than the one being left behind.
        $this->page = max(
            1,
            $page,
        );
        $this->page = min(
            $this->page,
            $this->getTotalPages(),
        );
    }

    /**
     * Narrowing the list restarts at the first page, so a reader is never left on a page that no longer exists.
     */
    protected function resetToFirstPage(): void
    {
        $this->page = 1;
    }

    /**
     * @return Paginator<T>
     */
    private function paginator(): Paginator
    {
        $page = max(
            1,
            $this->page,
        );

        if (
            null === $this->paginator
            || $this->paginatorPage !== $page
        ) {
            $this->paginator = $this->createPaginator(
                $page,
                $this->pageSize(),
            );
            $this->paginatorPage = $page;
        }

        return $this->paginator;
    }
}
