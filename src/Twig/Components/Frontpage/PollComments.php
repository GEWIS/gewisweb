<?php

declare(strict_types=1);

namespace App\Twig\Components\Frontpage;

use App\Entity\Decision\Member;
use App\Entity\Frontpage\Enums\PollCommentReactionType;
use App\Entity\Frontpage\Poll;
use App\Entity\Frontpage\PollComment;
use App\Entity\User\Enums\UserRoles;
use App\Entity\User\User;
use App\Repository\Frontpage\PollCommentRepository;
use App\Service\Frontpage\PollService;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use RuntimeException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

use function assert;
use function trim;

/**
 * The discussion underneath a poll: what members wrote, what they wrote back, and what they made of each other's
 * comments. All of it live, so writing, replying and reacting update the thread without a page reload.
 *
 * Members sign a comment with a name of their own choosing; only the board sees which member that was. Reactions are
 * shown as counts and nothing else, so responding to a comment says nothing about who you are.
 *
 * The class-level {@see IsGranted} guards the live actions over HTTP and nothing else, so the template that mounts
 * this is gated itself and every action below re-checks that the poll is still open.
 */
#[AsLiveComponent(
    name: 'Frontpage:PollComments',
    template: 'components/Frontpage/PollComments.html.twig',
)]
#[IsGranted(UserRoles::User->value)]
final class PollComments
{
    use ComponentToolsTrait;
    use DefaultActionTrait;

    #[LiveProp]
    public Poll $poll;

    /** The name the comment being written is signed with. */
    #[LiveProp(writable: true)]
    public string $author = '';

    #[LiveProp(writable: true)]
    public string $content = '';

    /** The comment the reader is answering, or null while they are not answering one. */
    #[LiveProp(writable: true)]
    public ?int $replyTo = null;

    #[LiveProp(writable: true)]
    public string $replyContent = '';

    /**
     * The comment whose replies are opened by the server rather than by the reader: the one just replied to, so what
     * was written does not land in a thread that is folded shut.
     */
    #[LiveProp]
    public ?int $expanded = null;

    /** Component-local, transient: what went wrong, shown on the render right after an action. */
    public ?string $problem = null;

    public function __construct(
        private readonly Security $security,
        private readonly PollService $pollService,
        private readonly PollCommentRepository $commentRepository,
        private readonly TranslatorInterface $translator,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    #[LiveAction]
    public function post(): void
    {
        $author = trim($this->author);
        $content = trim($this->content);

        if (
            '' === $author
            || '' === $content
        ) {
            $this->problem = $this->translator->trans('Fill in a name and something to say.');

            return;
        }

        $this->write(
            $author,
            $content,
        );

        if (null !== $this->problem) {
            return;
        }

        $this->content = '';
        $this->clearEditors();
    }

    #[LiveAction]
    public function startReply(
        #[LiveArg]
        int $comment,
    ): void {
        $this->replyTo = $comment;
        $this->replyContent = '';
    }

    #[LiveAction]
    public function cancelReply(): void
    {
        $this->replyTo = null;
        $this->replyContent = '';
    }

    #[LiveAction]
    public function reply(
        #[LiveArg]
        int $comment,
    ): void {
        $author = trim($this->author);
        $content = trim($this->replyContent);

        if (
            '' === $author
            || '' === $content
        ) {
            $this->problem = $this->translator->trans('Fill in a name and something to say.');

            return;
        }

        $this->write(
            $author,
            $content,
            $this->find($comment),
        );

        if (null !== $this->problem) {
            return;
        }

        $this->replyTo = null;
        $this->replyContent = '';
        $this->expanded = $comment;
        $this->clearEditors();
    }

    #[LiveAction]
    public function react(
        #[LiveArg]
        int $comment,
        #[LiveArg]
        string $type,
    ): ?Response {
        $reaction = PollCommentReactionType::tryFrom($type);
        if (null === $reaction) {
            throw new AccessDeniedException();
        }

        // Looked up outside the catch below: an AccessDeniedException is a RuntimeException too, and refusing a
        // comment that is not this poll's is not something to turn into a message about the poll being closed.
        $subject = $this->find($comment);

        try {
            $this->pollService->toggleReaction(
                $subject,
                $this->currentMember(),
                $reaction,
            );
        } catch (UniqueConstraintViolationException) {
            // A second tab reacted while this one was deciding and the unique index caught it. The entity manager is
            // closed now, so this thread cannot be re-rendered: send the reader back to the poll, which reloads the
            // discussion with the reaction on it from a fresh manager.
            return new RedirectResponse(
                $this->urlGenerator->generate(
                    'poll/view',
                    ['poll' => $this->poll->getId()],
                ),
            );
        } catch (RuntimeException) {
            $this->problem = $this->translator->trans('This poll is closed.');
        }

        return null;
    }

    /**
     * @return list<PollComment>
     */
    public function topLevelComments(): array
    {
        // One pass for the whole thread; without it every comment fetches its own replies, reactions and writer.
        $this->commentRepository->primeThread($this->poll);

        return $this->poll->getTopLevelComments();
    }

    /**
     * @return list<PollCommentReactionType>
     */
    public function reactionTypes(): array
    {
        return PollCommentReactionType::cases();
    }

    public function myReaction(PollComment $comment): ?PollCommentReactionType
    {
        return $comment->getReactionOf($this->currentMember());
    }

    public function showsMembers(): bool
    {
        return $this->security->isGranted(UserRoles::Board->value);
    }

    public function isOpen(): bool
    {
        return $this->poll->isActive();
    }

    /**
     * The comment boxes are editors the re-render is told to leave alone, so emptying the property behind one does
     * not empty the box. This says so out loud and the editor clears itself.
     */
    private function clearEditors(): void
    {
        $this->dispatchBrowserEvent('poll-comment:posted');
    }

    private function write(
        string $author,
        string $content,
        ?PollComment $parent = null,
    ): void {
        try {
            $this->pollService->addComment(
                $this->poll,
                $this->currentMember(),
                $author,
                $content,
                $parent,
            );
        } catch (RuntimeException) {
            $this->problem = $this->translator->trans('This poll is closed.');
        }
    }

    /**
     * Anything that is not this poll's comment is somebody reaching into another poll's thread through this one.
     */
    private function find(int $comment): PollComment
    {
        foreach ($this->poll->getComments() as $candidate) {
            if ($candidate->getId() !== $comment) {
                continue;
            }

            return $candidate;
        }

        throw new AccessDeniedException();
    }

    private function currentMember(): Member
    {
        $user = $this->security->getUser();
        assert($user instanceof User);

        return $user->getMember();
    }
}
