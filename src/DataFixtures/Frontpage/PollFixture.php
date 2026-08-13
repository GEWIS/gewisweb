<?php

declare(strict_types=1);

namespace App\DataFixtures\Frontpage;

use App\DataFixtures\Decision\MemberFixture;
use App\DataFixtures\User\UserFixture;
use App\Entity\Application\Enums\RevisionStatus;
use App\Entity\Decision\Member as MemberModel;
use App\Entity\Frontpage\Enums\PollCommentReactionType;
use App\Entity\Frontpage\FrontpageLocalisedText;
use App\Entity\Frontpage\Poll;
use App\Entity\Frontpage\PollComment;
use App\Entity\Frontpage\PollCommentReaction;
use App\Entity\Frontpage\PollOption;
use App\Entity\Frontpage\PollRevision;
use App\Entity\Frontpage\PollRevisionComment;
use App\Entity\Frontpage\PollVote;
use App\Entity\User\User;
use DateTime;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Override;

/**
 * One of every kind of poll there is: the one that is running, one that has closed, one the board still has to look at
 * and one it turned down. Between them the front page, the archive, the review queue and the "ask again" route all
 * have something to show without anybody clicking a poll through the workflow by hand.
 *
 * Statuses are set directly rather than driven through the state machine, the way the other review fixtures do it.
 */
class PollFixture extends Fixture implements DependentFixtureInterface
{
    #[Override]
    public function load(ObjectManager $manager): void
    {
        $this->live($manager);
        $this->closed($manager);
        $this->awaitingReview($manager);
        $this->rejected($manager);

        $manager->flush();
    }

    /**
     * @return class-string<Fixture>[]
     */
    #[Override]
    public function getDependencies(): array
    {
        return [
            MemberFixture::class,
            UserFixture::class,
        ];
    }

    private function live(ObjectManager $manager): void
    {
        $poll = $this->poll(
            $manager,
            8005,
            'When should the weekly social drink start?',
            'Hoe laat zou de wekelijkse borrel moeten beginnen?',
            [
                [
                    'Thursday at 16:30',
                    'Donderdag om 16:30',
                ],
                [
                    'Friday afternoon',
                    'Vrijdagmiddag',
                ],
                [
                    'Keep it as it is',
                    'Houden zoals het is',
                ],
            ],
            RevisionStatus::Approved,
            new DateTime('+3 weeks'),
        );

        $revision = $poll->getCurrentRevision();
        if (null === $revision) {
            return;
        }

        $options = $revision->getOptions()->getValues();

        // Nine members answered, so the bars are not all the same length.
        $tallies = [
            8005 => 0,
            8006 => 0,
            8007 => 0,
            8008 => 0,
            8009 => 1,
            8010 => 1,
            8011 => 1,
            8012 => 2,
            8013 => 0,
        ];

        foreach ($tallies as $lidnr => $index) {
            $vote = new PollVote();
            $vote->setPoll($poll);
            $vote->setPollOption($options[$index]);
            $vote->setRespondent($this->member($lidnr));
            $manager->persist($vote);
        }

        $comment = $this->comment(
            $manager,
            $poll,
            8006,
            'Anonymous Thirst',
            'Half past four is too early for anybody with a lecture until five.',
            new DateTime('-2 days'),
        );

        $reply = $this->comment(
            $manager,
            $poll,
            8007,
            'Late Riser',
            'Friday works better for me, but I would be there either way.',
            new DateTime('-1 day'),
            $comment,
        );

        $this->comment(
            $manager,
            $poll,
            8008,
            'Second Thought',
            'Friday afternoon runs into the weekend for anybody travelling home.',
            new DateTime('-1 day'),
            $reply,
        );

        $reactions = [
            8008 => PollCommentReactionType::Like,
            8009 => PollCommentReactionType::Like,
            8010 => PollCommentReactionType::Insightful,
        ];

        foreach ($reactions as $lidnr => $type) {
            $reaction = new PollCommentReaction();
            $reaction->setMember($this->member($lidnr));
            $reaction->setType($type);
            $comment->addReaction($reaction);
            $manager->persist($reaction);
        }
    }

    /**
     * A poll that has run its course, so the archive is not a list of one.
     */
    private function closed(ObjectManager $manager): void
    {
        $poll = $this->poll(
            $manager,
            8006,
            'Which board photo should hang in the association room?',
            'Welke bestuursfoto moet er in de GEWIS-ruimte hangen?',
            [
                [
                    'The one on the stairs',
                    'Die op de trap',
                ],
                [
                    'The one in the lecture hall',
                    'Die in de collegezaal',
                ],
            ],
            RevisionStatus::Approved,
            new DateTime('-2 months'),
        );

        $revision = $poll->getCurrentRevision();
        if (null === $revision) {
            return;
        }

        $options = $revision->getOptions()->getValues();

        // Most of it was answered long enough ago to have been anonymised already; the rest is still attributable,
        // which is what the anonymisation run has to turn into more of the former without changing either tally.
        $options[0]->setAnonymousVotes(14);
        $options[1]->setAnonymousVotes(23);

        $tallies = [
            8014 => 0,
            8015 => 1,
            8016 => 1,
        ];

        foreach ($tallies as $lidnr => $index) {
            $vote = new PollVote();
            $vote->setPoll($poll);
            $vote->setPollOption($options[$index]);
            $vote->setRespondent($this->member($lidnr));
            $manager->persist($vote);
        }

        $comment = $this->comment(
            $manager,
            $poll,
            8017,
            'Wall Watcher',
            'The one on the stairs it is, then.',
            new DateTime('-2 months'),
        );

        $reaction = new PollCommentReaction();
        $reaction->setMember($this->member(8018));
        $reaction->setType(PollCommentReactionType::Like);
        $comment->addReaction($reaction);
        $manager->persist($reaction);
    }

    /**
     * A question the board still has to decide on, so the review queue and the review screen have something in them.
     */
    private function awaitingReview(ObjectManager $manager): void
    {
        $this->poll(
            $manager,
            8007,
            'Should the association room get a second coffee machine?',
            'Moet er een tweede koffiezetapparaat in de GEWIS-ruimte komen?',
            [
                [
                    'Yes, the queue is too long',
                    'Ja, de rij is te lang',
                ],
                [
                    'No, spend the money elsewhere',
                    'Nee, geef het geld ergens anders aan uit',
                ],
                [
                    'Only if it makes tea as well',
                    'Alleen als het ook thee zet',
                ],
            ],
            RevisionStatus::Submitted,
            null,
        );
    }

    /**
     * A question the board turned down, which is what the "ask again" route on a poll's own page needs.
     */
    private function rejected(ObjectManager $manager): void
    {
        $poll = $this->poll(
            $manager,
            8008,
            'Who is the worst board member?',
            'Wie is het slechtste bestuurslid?',
            [
                [
                    'All of them',
                    'Allemaal',
                ],
                [
                    'None of them',
                    'Niemand',
                ],
            ],
            RevisionStatus::Rejected,
            null,
        );

        $revision = $poll->getCurrentRevision();
        if (null === $revision) {
            return;
        }

        $revision->setReviewer($this->member(8000));
        $revision->setReviewedAt(new DateTime('-1 week'));

        $feedback = new PollRevisionComment();
        $feedback->attachTo($revision);
        $feedback->setAuthor($this->getReference(
            'user-8000',
            User::class,
        ));
        $feedback->setBody('We are not putting a question about named people to the whole association.');
        $manager->persist($feedback);
    }

    /**
     * @param list<array{string, string}> $answers
     */
    private function poll(
        ObjectManager $manager,
        int $creator,
        string $questionEN,
        string $questionNL,
        array $answers,
        RevisionStatus $status,
        ?DateTime $expiryDate,
    ): Poll {
        $poll = new Poll();
        $poll->setCreator($this->member($creator));
        $poll->setExpiryDate($expiryDate);

        $revision = new PollRevision();
        $revision->setStatus($status);
        $revision->setAuthor($this->member($creator));

        // What the board still has to look at is dated, since a queue is ordered and coloured by how long something
        // has been waiting on it.
        if (
            RevisionStatus::Submitted === $status
            || RevisionStatus::InReview === $status
        ) {
            $revision->setSubmittedAt(new DateTime('-2 days'));
        }

        $revision->setQuestion(new FrontpageLocalisedText(
            $questionEN,
            $questionNL,
        ));

        foreach ($answers as [$answerEN, $answerNL]) {
            $option = new PollOption();
            $option->setText(new FrontpageLocalisedText(
                $answerEN,
                $answerNL,
            ));
            $revision->addOption($option);
        }

        $poll->addRevision($revision);
        $poll->setCurrentRevision($revision);

        if (RevisionStatus::Approved === $status) {
            $poll->setLiveRevision($revision);
            $revision->setReviewer($this->member(8000));
            $revision->setReviewedAt(new DateTime('-1 month'));
        }

        $manager->persist($poll);
        $manager->persist($revision);

        return $poll;
    }

    private function comment(
        ObjectManager $manager,
        Poll $poll,
        int $lidnr,
        string $author,
        string $content,
        DateTime $createdOn,
        ?PollComment $parent = null,
    ): PollComment {
        $comment = new PollComment();
        $comment->setUser($this->member($lidnr));
        $comment->setAuthor($author);
        $comment->setContent($content);
        $comment->setCreatedOn($createdOn);
        $comment->setParent($parent);
        $poll->addComment($comment);

        $manager->persist($comment);

        return $comment;
    }

    private function member(int $lidnr): MemberModel
    {
        return $this->getReference(
            'member-' . $lidnr,
            MemberModel::class,
        );
    }
}
