<?php

declare(strict_types=1);

namespace App\Service\Photo;

use App\Entity\Decision\Member;
use App\Entity\Photo\Photo;
use App\Entity\Photo\Vote;
use App\Repository\Photo\VoteRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Records a member's vote for the photo of the week. Who may vote (members only, never graduates) is decided by
 * {@see \App\Security\Photo\PhotoVoter}; this service only makes the vote idempotent.
 */
final readonly class VoteService
{
    public function __construct(
        private VoteRepository $voteRepository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function castVote(
        Photo $photo,
        Member $voter,
    ): void {
        if (
            null !== $this->voteRepository->findVote(
                (int) $photo->getId(),
                $voter->getLidnr(),
            )
        ) {
            return;
        }

        $this->entityManager->persist(new Vote($photo, $voter));
        $this->entityManager->flush();
    }
}
