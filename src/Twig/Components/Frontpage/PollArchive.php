<?php

declare(strict_types=1);

namespace App\Twig\Components\Frontpage;

use App\Entity\Decision\Member;
use App\Entity\Frontpage\Poll;
use App\Entity\User\User;
use App\Repository\Frontpage\PollCommentRepository;
use App\Repository\Frontpage\PollRepository;
use App\Twig\Components\Application\AbstractPaginatedOverview;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Override;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;

/**
 * Every question the association has ever been asked. There are well over a thousand of them, so the archive is
 * searched and paged rather than listed: the search reaches the answers as well as the question, since a poll is
 * usually remembered by what could be answered rather than by how it was asked.
 *
 * Narrowing to what the reader did or did not answer is offered only while they are signed in; to a passer-by it
 * would mean nothing.
 *
 * @extends AbstractPaginatedOverview<Poll>
 */
#[AsLiveComponent(
    name: 'Frontpage:PollArchive',
    template: 'components/Frontpage/PollArchive.html.twig',
)]
final class PollArchive extends AbstractPaginatedOverview
{
    #[LiveProp(
        writable: true,
        url: true,
        onUpdated: 'onFilterUpdated',
    )]
    public string $search = '';

    /** 'all', 'answered' or 'unanswered'; anything else reads as 'all'. */
    #[LiveProp(
        writable: true,
        url: true,
        onUpdated: 'onFilterUpdated',
    )]
    public string $answered = 'all';

    #[LiveProp(
        writable: true,
        url: true,
        onUpdated: 'onFilterUpdated',
    )]
    public bool $oldestFirst = false;

    /** @var list<Poll>|null */
    private ?array $polls = null;

    public function __construct(
        private readonly PollRepository $pollRepository,
        private readonly PollCommentRepository $commentRepository,
        private readonly Security $security,
    ) {
    }

    public function onFilterUpdated(): void
    {
        $this->resetToFirstPage();
    }

    /**
     * @return list<Poll>
     */
    public function getPolls(): array
    {
        if (null !== $this->polls) {
            return $this->polls;
        }

        $polls = $this->getRows();
        $this->pollRepository->primeResults($polls);

        return $this->polls = $polls;
    }

    /**
     * How many top-level comments each shown poll has, keyed by poll id.
     *
     * @return array<int, int>
     */
    public function getCommentCounts(): array
    {
        return $this->commentRepository->countTopLevelForPolls($this->getPolls());
    }

    public function knowsTheReader(): bool
    {
        return null !== $this->member();
    }

    /**
     * @return Paginator<Poll>
     */
    #[Override]
    protected function createPaginator(
        int $page,
        int $pageSize,
    ): Paginator {
        return $this->pollRepository->getArchivePaginator(
            $page,
            $pageSize,
            $this->search,
            $this->member(),
            match ($this->answered) {
                'answered' => true,
                'unanswered' => false,
                default => null,
            },
            $this->oldestFirst,
        );
    }

    private function member(): ?Member
    {
        $user = $this->security->getUser();

        return $user instanceof User
            ? $user->getMember()
            : null;
    }
}
