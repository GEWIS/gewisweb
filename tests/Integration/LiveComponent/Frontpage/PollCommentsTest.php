<?php

declare(strict_types=1);

namespace App\Tests\Integration\LiveComponent\Frontpage;

use App\Entity\Frontpage\Enums\PollCommentReactionType;
use App\Entity\Frontpage\Poll;
use App\Entity\User\User;
use App\Repository\Frontpage\PollRepository;
use App\Service\Frontpage\PollService;
use App\Tests\Integration\DatabaseTestCase;
use App\Twig\Components\Frontpage\PollComments;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use function count;

/**
 * The discussion writes to the database from a live action, so it re-checks for itself rather than trusting the page
 * that embedded it. These exercise the real component with its real services after authenticating on the token
 * storage, which is what it reads.
 *
 * Driving it over the live-component HTTP endpoint is not viable here: the session guard force-logs-out any session
 * with no managed-session row behind it, so the class-level `#[IsGranted]` is enforced at that layer rather than
 * exercised here; what each action asserts for itself is.
 */
final class PollCommentsTest extends DatabaseTestCase
{
    public function testPostingAddsAComment(): void
    {
        $component = $this->component(8100);
        $before = count($component->topLevelComments());

        $component->author = 'Somebody';
        $component->content = 'Half past four suits me.';
        $component->post();

        self::assertNull($component->problem);
        self::assertCount(
            $before + 1,
            $component->topLevelComments(),
        );
        // The box is emptied so the next comment does not start with the last one still in it.
        self::assertSame(
            '',
            $component->content,
        );
    }

    public function testACommentWithoutANameOrTextIsRefused(): void
    {
        $component = $this->component(8101);
        $before = count($component->topLevelComments());

        $component->author = '  ';
        $component->content = 'Something';
        $component->post();

        self::assertNotNull($component->problem);
        self::assertCount(
            $before,
            $component->topLevelComments(),
        );
    }

    public function testAReplyIsFiledUnderTheCommentItAnswers(): void
    {
        $component = $this->component(8102);
        $top = $component->topLevelComments()[0];
        $before = count($top->getReplies());

        $component->author = 'Somebody';
        $component->startReply($top->getId() ?? 0);
        $component->replyContent = 'Not for me.';
        $component->reply($top->getId() ?? 0);

        self::assertNull($component->problem);
        self::assertCount(
            $before + 1,
            $top->getReplies(),
        );
        // The reply box closes again, so the next click opens a fresh one.
        self::assertNull($component->replyTo);
    }

    public function testReactingTwiceWithTheSameThingTakesItBack(): void
    {
        $component = $this->component(8103);
        $comment = $component->topLevelComments()[0];

        $component->react(
            $comment->getId() ?? 0,
            PollCommentReactionType::Love->value,
        );
        self::assertSame(
            PollCommentReactionType::Love,
            $component->myReaction($comment),
        );

        $component->react(
            $comment->getId() ?? 0,
            PollCommentReactionType::Love->value,
        );
        self::assertNull($component->myReaction($comment));
    }

    public function testACommentFromAnotherPollCannotBeReactedToThroughThisThread(): void
    {
        $component = $this->component(8104);

        $this->expectException(AccessDeniedException::class);
        $component->react(
            0,
            PollCommentReactionType::Like->value,
        );
    }

    public function testAClosedPollFreezesTheThread(): void
    {
        $component = $this->component(8105);
        $comment = $component->topLevelComments()[0];
        self::getContainer()->get(PollService::class)->softExpire($component->poll);

        self::assertFalse($component->isOpen());

        $component->author = 'Somebody';
        $component->content = 'Too late.';
        $component->post();
        self::assertNotNull($component->problem);

        $component->problem = null;
        $component->react(
            $comment->getId() ?? 0,
            PollCommentReactionType::Like->value,
        );
        self::assertNotNull($component->problem);
    }

    /**
     * A pseudonym is the whole point of signing a comment yourself, so who wrote it is the board's to see and nobody
     * else's.
     */
    public function testOnlyTheBoardSeesWhichMemberWroteAComment(): void
    {
        self::assertFalse($this->component(8106)->showsMembers());
        self::assertTrue($this->component(
            8000,
            ['ROLE_BOARD'],
        )->showsMembers());
    }

    /**
     * @param string[] $roles
     */
    private function component(
        int $lidnr,
        array $roles = ['ROLE_USER'],
    ): PollComments {
        $user = $this->entityManager->getRepository(User::class)->find($lidnr);
        self::assertInstanceOf(
            User::class,
            $user,
        );

        self::getContainer()->get('security.token_storage')->setToken(new UsernamePasswordToken(
            $user,
            'main',
            $roles,
        ));

        $component = self::getContainer()->get(PollComments::class);
        $component->poll = $this->livePoll();

        return $component;
    }

    private function livePoll(): Poll
    {
        $poll = self::getContainer()->get(PollRepository::class)->findCurrentPoll();
        self::assertInstanceOf(
            Poll::class,
            $poll,
            'The seed is expected to contain a running poll with a discussion under it.',
        );

        return $poll;
    }
}
