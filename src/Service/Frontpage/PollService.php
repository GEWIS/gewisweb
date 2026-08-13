<?php

declare(strict_types=1);

namespace App\Service\Frontpage;

use App\Entity\Decision\Member;
use App\Entity\Frontpage\Enums\PollCommentReactionType;
use App\Entity\Frontpage\Poll;
use App\Entity\Frontpage\PollComment;
use App\Entity\Frontpage\PollCommentReaction;
use App\Entity\Frontpage\PollOption;
use App\Entity\Frontpage\PollRevision;
use App\Entity\Frontpage\PollVote;
use App\Repository\Frontpage\PollCommentReactionRepository;
use App\Repository\Frontpage\PollVoteRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Workflow\WorkflowInterface;

/**
 * Everything that happens to a poll after it has been written: asking for it, answering it, talking underneath it and
 * taking it down again.
 */
final readonly class PollService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private PollVoteRepository $pollVoteRepository,
        private PollCommentReactionRepository $reactionRepository,
        #[Target('revisionStateMachine')]
        private WorkflowInterface $revisionStateMachine,
    ) {
    }

    /**
     * Ask the board a question. A poll is written and submitted in one go, so there is no draft to leave behind: the
     * revision the form filled in is persisted and handed straight to the board.
     *
     * A question that was turned down is asked again by continuing that poll's chain rather than starting a new one,
     * so the board can read the new wording against what it refused.
     *
     * The revision has to exist before it is submitted: the listener that tells the board silently skips a revision
     * without an id, so submitting first loses the notification without saying so.
     */
    public function requestPoll(
        PollRevision $revision,
        Member $creator,
        ?Poll $previous = null,
    ): Poll {
        $poll = $previous ?? new Poll();

        if (null === $previous) {
            $poll->setCreator($creator);
        }

        $revision->setAuthor($creator);
        $poll->addRevision($revision);

        $head = $poll->getCurrentRevision();
        if (null !== $head) {
            $revision->setPreviousRevision($head);
            $revision->setRevisionNumber($head->getRevisionNumber() + 1);
        }

        $poll->setCurrentRevision($revision);

        $this->entityManager->persist($poll);
        $this->entityManager->persist($revision);
        $this->entityManager->flush();

        $this->revisionStateMachine->apply(
            $revision,
            'submit',
        );
        $this->entityManager->flush();

        return $poll;
    }

    /**
     * Answer a poll. One answer per member per poll, which the database holds as well: two requests racing each other
     * both pass the check below and the second one is turned away by the constraint rather than counted twice.
     *
     * That refusal reaches the caller as the database's own exception. It leaves the entity manager closed, so the
     * caller cannot carry on rendering and has to send the reader somewhere with a fresh one.
     */
    public function submitVote(
        Poll $poll,
        PollOption $option,
        Member $member,
    ): void {
        if (!$poll->isActive()) {
            throw new RuntimeException('This poll is closed.');
        }

        if ($option->getRevision() !== $poll->getLiveRevision()) {
            throw new RuntimeException('That answer does not belong to this poll.');
        }

        if (
            $this->hasVoted(
                $poll,
                $member,
            )
        ) {
            throw new RuntimeException('You have already answered this poll.');
        }

        $vote = new PollVote();
        $vote->setPoll($poll);
        $vote->setPollOption($option);
        $vote->setRespondent($member);

        $this->entityManager->persist($vote);
        $this->entityManager->flush();
    }

    private function hasVoted(
        Poll $poll,
        Member $member,
    ): bool {
        return null !== $this->votedOption(
            $poll,
            $member,
        );
    }

    public function votedOption(
        Poll $poll,
        Member $member,
    ): ?PollOption {
        return $this->pollVoteRepository->findVote(
            (int) $poll->getId(),
            $member->getLidnr(),
        )?->getPollOption();
    }

    /**
     * Write underneath a poll, either on its own or in answer to something already there.
     */
    public function addComment(
        Poll $poll,
        Member $member,
        string $author,
        string $content,
        ?PollComment $parent = null,
    ): PollComment {
        if (!$poll->isActive()) {
            throw new RuntimeException('This poll is closed.');
        }

        if (
            null !== $parent
            && $parent->getPoll() !== $poll
        ) {
            throw new RuntimeException('That comment belongs to another poll.');
        }

        $comment = new PollComment();
        $comment->setUser($member);
        $comment->setAuthor($author);
        $comment->setContent($content);
        $comment->setCreatedOn(new DateTime());
        $comment->setParent($parent);
        $poll->addComment($comment);

        $this->entityManager->persist($comment);
        $this->entityManager->flush();

        return $comment;
    }

    /**
     * Reacting again with the same thing takes the reaction back, which is the only way to undo one.
     *
     * One reaction per member per comment is held by the database as well, and a race over a first reaction reaches
     * the caller the same way a race over an answer does.
     */
    public function toggleReaction(
        PollComment $comment,
        Member $member,
        PollCommentReactionType $type,
    ): void {
        if (!$comment->getPoll()->isActive()) {
            throw new RuntimeException('This poll is closed.');
        }

        $existing = $this->reactionRepository->findOneByCommentAndMember(
            $comment,
            $member,
        );

        if (null === $existing) {
            $reaction = new PollCommentReaction();
            $reaction->setMember($member);
            $reaction->setType($type);
            $comment->addReaction($reaction);

            $this->entityManager->persist($reaction);
            $this->entityManager->flush();

            return;
        }

        if ($existing->getType() === $type) {
            $comment->removeReaction($existing);
            $this->entityManager->remove($existing);
        } else {
            $existing->setType($type);
        }

        $this->entityManager->flush();
    }

    /**
     * Take a poll off the website without throwing away what it asked or how it was answered: it closes today, which
     * is what a poll reaching its date does anyway.
     */
    public function softExpire(Poll $poll): void
    {
        $poll->setExpiryDate(new DateTime('today'));
        $this->entityManager->flush();
    }
}
