<?php

declare(strict_types=1);

namespace App\Tests\Integration\Service\Frontpage;

use App\Entity\Application\Enums\RevisionStatus;
use App\Entity\Decision\Member;
use App\Entity\Frontpage\Enums\PollCommentReactionType;
use App\Entity\Frontpage\FrontpageLocalisedText;
use App\Entity\Frontpage\Poll;
use App\Entity\Frontpage\PollOption;
use App\Entity\Frontpage\PollRevision;
use App\Entity\Frontpage\PollVote;
use App\Entity\User\User;
use App\Repository\Frontpage\PollRepository;
use App\Service\Frontpage\PollService;
use App\Service\User\GdprService;
use App\Tests\Integration\DatabaseTestCase;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use RuntimeException;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

use function array_column;

/**
 * What happens to a poll once it exists, against a real database: the constraint that holds a member to one answer,
 * the re-parenting that keeps a discussion one level deep, and the fact that taking a poll down keeps everything it
 * asked and was answered.
 */
final class PollServiceTest extends DatabaseTestCase
{
    public function testRequestingAPollHandsItStraightToTheBoard(): void
    {
        $this->authenticate();

        $poll = $this->service()->requestPoll(
            $this->revision(
                'Should this test pass?',
                [
                    'Yes',
                    'Obviously',
                ],
            ),
            $this->member(8005),
        );

        $head = $poll->getCurrentRevision();
        self::assertInstanceOf(
            PollRevision::class,
            $head,
        );
        self::assertSame(
            RevisionStatus::Submitted,
            $head->getStatus(),
        );
        // Nothing is public until the board says so, and a poll has no date until then either.
        self::assertNull($poll->getLiveRevision());
        self::assertNull($poll->getExpiryDate());
    }

    /**
     * A question that was turned down continues its own chain, so the board reads the new wording against what it
     * refused rather than as something it has never seen.
     */
    public function testAskingAgainContinuesTheSameChain(): void
    {
        $rejected = $this->rejectedPoll();
        $previousHead = $rejected->getCurrentRevision();

        // Only whoever asked gets to ask again, so the workflow guards read that member behind the request.
        $this->authenticate($rejected->getCreator()->getLidnr());

        $poll = $this->service()->requestPoll(
            $this->revision(
                'Which board member deserves a compliment?',
                [
                    'All of them',
                    'The treasurer',
                ],
            ),
            $rejected->getCreator(),
            $rejected,
        );

        self::assertSame(
            $rejected,
            $poll,
        );

        $head = $poll->getCurrentRevision();
        self::assertInstanceOf(
            PollRevision::class,
            $head,
        );
        self::assertSame(
            $previousHead,
            $head->getPreviousRevision(),
        );
        self::assertSame(
            2,
            $head->getRevisionNumber(),
        );
    }

    public function testAMemberOnlyGetsOneAnswer(): void
    {
        $poll = $this->livePoll();
        $options = $poll->getOptions()->getValues();
        $member = $this->member(8100);

        $this->service()->submitVote(
            $poll,
            $options[0],
            $member,
        );

        self::assertNotNull($this->service()->votedOption(
            $poll,
            $member,
        ));

        $this->expectException(RuntimeException::class);
        $this->service()->submitVote(
            $poll,
            $options[1],
            $member,
        );
    }

    /**
     * The check above is not the only thing holding a member to one answer: two requests racing each other both pass
     * it, and the second is turned away by the unique index. Writing the second vote by hand is that race in one
     * process, since going through the service is exactly what the losing request has already got past. What comes
     * back is the database's own exception, which is what tells the widget it cannot render its way out of this.
     */
    public function testASecondAnswerIsRefusedByTheDatabaseToo(): void
    {
        $poll = $this->livePoll();
        $options = $poll->getOptions()->getValues();
        $member = $this->member(8101);

        $this->service()->submitVote(
            $poll,
            $options[0],
            $member,
        );

        $second = new PollVote();
        $second->setPoll($poll);
        $second->setPollOption($options[1]);
        $second->setRespondent($member);
        $this->entityManager->persist($second);

        $this->expectException(UniqueConstraintViolationException::class);
        $this->entityManager->flush();
    }

    public function testAnswerFromAnotherPollIsRefused(): void
    {
        $poll = $this->livePoll();
        $other = $this->rejectedPoll()->getCurrentRevision();
        self::assertInstanceOf(
            PollRevision::class,
            $other,
        );

        $this->expectException(RuntimeException::class);
        $this->service()->submitVote(
            $poll,
            $other->getOptions()->getValues()[0],
            $this->member(8102),
        );
    }

    public function testAClosedPollTakesNoMoreAnswers(): void
    {
        $poll = $this->livePoll();
        $option = $poll->getOptions()->getValues()[0];
        $this->service()->softExpire($poll);

        self::assertFalse($poll->isActive());

        $this->expectException(RuntimeException::class);
        $this->service()->submitVote(
            $poll,
            $option,
            $this->member(8103),
        );
    }

    /**
     * Taking a poll down moves its date rather than deleting anything, so the question, the answers and the tallies
     * are all still there afterwards.
     */
    public function testTakingAPollDownKeepsWhatItAsked(): void
    {
        $poll = $this->livePoll();
        $before = $poll->getTotalVotesCount();
        $question = $poll->getQuestion();

        $this->service()->softExpire($poll);

        self::assertSame(
            $before,
            $poll->getTotalVotesCount(),
        );
        self::assertSame(
            $question,
            $poll->getQuestion(),
        );
    }

    public function testAReplyIsFiledUnderTheCommentItAnswersHoweverDeep(): void
    {
        $poll = $this->livePoll();
        $service = $this->service();

        $top = $service->addComment(
            $poll,
            $this->member(8104),
            'First',
            'Half past four is fine.',
        );
        $reply = $service->addComment(
            $poll,
            $this->member(8105),
            'Second',
            'Not for me.',
            $top,
        );
        $deeper = $service->addComment(
            $poll,
            $this->member(8106),
            'Third',
            'Nor for me.',
            $reply,
        );

        self::assertSame(
            $top,
            $reply->getParent(),
        );
        self::assertSame(
            $reply,
            $deeper->getParent(),
        );
        self::assertSame(
            [$reply],
            $top->getReplies()->toArray(),
        );
    }

    public function testAReactionCanBeChangedAndTakenBack(): void
    {
        $poll = $this->livePoll();
        $service = $this->service();
        $member = $this->member(8107);

        $comment = $service->addComment(
            $poll,
            $this->member(8108),
            'Someone',
            'Worth thinking about.',
        );

        $service->toggleReaction(
            $comment,
            $member,
            PollCommentReactionType::Like,
        );
        self::assertSame(
            PollCommentReactionType::Like,
            $comment->getReactionOf($member),
        );

        $service->toggleReaction(
            $comment,
            $member,
            PollCommentReactionType::Funny,
        );
        self::assertSame(
            PollCommentReactionType::Funny,
            $comment->getReactionOf($member),
        );

        $service->toggleReaction(
            $comment,
            $member,
            PollCommentReactionType::Funny,
        );
        self::assertNull($comment->getReactionOf($member));
    }

    public function testAClosedPollFreezesItsDiscussion(): void
    {
        $poll = $this->livePoll();
        $service = $this->service();
        $comment = $service->addComment(
            $poll,
            $this->member(8109),
            'Someone',
            'Still open for now.',
        );

        $service->softExpire($poll);

        $this->expectException(RuntimeException::class);
        $service->toggleReaction(
            $comment,
            $this->member(8110),
            PollCommentReactionType::Like,
        );
    }

    /**
     * The export used to read the polls a member approved off the poll itself; it now reads them off the revisions
     * they decided on, which has to keep working for a reviewer who has decided something.
     */
    public function testTheDataExportStillNamesThePollsAMemberDecidedOn(): void
    {
        $export = self::getContainer()->get(GdprService::class)->collectMemberData($this->member(8000));

        self::assertArrayHasKey(
            'reviewed',
            $export['polls'],
        );
        self::assertNotEmpty($export['polls']['reviewed']);
        self::assertNotContains(
            null,
            array_column(
                $export['polls']['reviewed'],
                'question',
            ),
        );
    }

    private function service(): PollService
    {
        return self::getContainer()->get(PollService::class);
    }

    /**
     * @param list<string> $answers
     */
    private function revision(
        string $question,
        array $answers,
    ): PollRevision {
        $revision = new PollRevision();
        $revision->setQuestion(new FrontpageLocalisedText($question));

        foreach ($answers as $answer) {
            $option = new PollOption();
            $option->setText(new FrontpageLocalisedText($answer));
            $revision->addOption($option);
        }

        return $revision;
    }

    private function livePoll(): Poll
    {
        $poll = self::getContainer()->get(PollRepository::class)->findCurrentPoll();
        self::assertInstanceOf(
            Poll::class,
            $poll,
            'The seed is expected to contain a running poll.',
        );

        return $poll;
    }

    private function rejectedPoll(): Poll
    {
        foreach (self::getContainer()->get(PollRepository::class)->findAll() as $poll) {
            if (RevisionStatus::Rejected !== $poll->getCurrentRevision()?->getStatus()) {
                continue;
            }

            return $poll;
        }

        self::fail('The seed is expected to contain a poll the board turned down.');
    }

    private function member(int $lidnr): Member
    {
        $member = $this->entityManager->find(
            Member::class,
            $lidnr,
        );
        self::assertInstanceOf(
            Member::class,
            $member,
        );

        return $member;
    }

    /**
     * Submitting runs through the workflow, whose guards read the member behind the request.
     */
    private function authenticate(int $lidnr = 8005): void
    {
        $user = $this->entityManager->find(
            User::class,
            $lidnr,
        );
        self::assertInstanceOf(
            User::class,
            $user,
        );

        self::getContainer()->get('security.token_storage')->setToken(new UsernamePasswordToken(
            $user,
            'main',
            ['ROLE_USER'],
        ));
    }
}
