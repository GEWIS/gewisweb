<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * phpcs:disable Generic.Files.LineLength.TooLong
 * phpcs:disable SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall
 */
final class Version20260813121428 extends AbstractMigration
{
    /**
     * The tables a revision already lived in. A poll's own is created below, with the same column in it.
     */
    private const array REVISION_TABLES = [
        'ActivityRevision',
        'CompanyRevision',
        'OrganInformationRevision',
        'VacancyRevision',
    ];

    public function getDescription(): string
    {
        return 'Put the questions members ask the association through the same review workflow as an activity or a body page. A poll used to be approved by writing down who approved it, which left no room for saying no and no record of why. The question and its answers now live on a revision the board decides on, and the poll itself keeps the votes, the discussion and the date it closes on. What was approved becomes an approved revision that stays live; what nobody ever approved becomes a rejected one, because that is what happened to it. The board fills in the closing date when it agrees to a question, so approving a poll is also scheduling it. Every revision, of whatever kind, now also records when it was handed to its reviewers, which is not the same moment as when it was written: a draft can be worked on for days before it is submitted, and the review queues say how long the board has had something. Revisions that were already submitted are given the moment they were written, which is the closest thing to it that was kept.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE PollRevision (
              id INT AUTO_INCREMENT NOT NULL,
              poll_id INT NOT NULL,
              previousRevision_id INT DEFAULT NULL,
              question_id INT NOT NULL,
              author_id INT DEFAULT NULL,
              authorCompanyUser_id INT DEFAULT NULL,
              reviewer_id INT DEFAULT NULL,
              lastEditedBy_id INT DEFAULT NULL,
              lastEditedByCompanyUser_id INT DEFAULT NULL,
              status VARCHAR(255) NOT NULL,
              revisionNumber INT NOT NULL,
              reviewedAt DATETIME DEFAULT NULL,
              submittedAt DATETIME DEFAULT NULL,
              version INT DEFAULT 1 NOT NULL,
              createdAt DATETIME NOT NULL,
              updatedAt DATETIME NOT NULL,
              INDEX IDX_439C0203C947C0F (poll_id),
              INDEX IDX_439C0208F2D4199 (previousRevision_id),
              UNIQUE INDEX UNIQ_439C0201E27F6BF (question_id),
              INDEX IDX_439C020F675F31B (author_id),
              INDEX IDX_439C020FD16CEE4 (authorCompanyUser_id),
              INDEX IDX_439C02070574616 (reviewer_id),
              INDEX IDX_439C020A19E445F (lastEditedBy_id),
              INDEX IDX_439C020102DD120 (lastEditedByCompanyUser_id),
              INDEX poll_revision_chain_idx (poll_id, revisionNumber),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
            SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE PollRevisionComment (
              id INT AUTO_INCREMENT NOT NULL,
              revision_id INT NOT NULL,
              author_id INT DEFAULT NULL,
              authorCompanyUser_id INT DEFAULT NULL,
              body LONGTEXT NOT NULL,
              createdAt DATETIME NOT NULL,
              updatedAt DATETIME NOT NULL,
              INDEX IDX_A6CF01781DFA7C8F (revision_id),
              INDEX IDX_A6CF0178F675F31B (author_id),
              INDEX IDX_A6CF0178FD16CEE4 (authorCompanyUser_id),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
            SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE PollCommentReaction (
              id INT AUTO_INCREMENT NOT NULL,
              comment_id INT NOT NULL,
              member_lidnr INT DEFAULT NULL,
              type VARCHAR(255) NOT NULL,
              createdAt DATETIME NOT NULL,
              updatedAt DATETIME NOT NULL,
              INDEX IDX_68E4C8D0F8697D13 (comment_id),
              INDEX IDX_68E4C8D0B44475EE (member_lidnr),
              UNIQUE INDEX poll_comment_reaction_uniq (comment_id, member_lidnr),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
            SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE PollRevision
              ADD CONSTRAINT FK_439C0203C947C0F FOREIGN KEY (poll_id) REFERENCES Poll (id),
              ADD CONSTRAINT FK_439C0208F2D4199 FOREIGN KEY (previousRevision_id) REFERENCES PollRevision (id),
              ADD CONSTRAINT FK_439C0201E27F6BF FOREIGN KEY (question_id) REFERENCES FrontpageLocalisedText (id),
              ADD CONSTRAINT FK_439C020F675F31B FOREIGN KEY (author_id) REFERENCES Member (lidnr),
              ADD CONSTRAINT FK_439C020FD16CEE4 FOREIGN KEY (authorCompanyUser_id) REFERENCES CompanyUser (id) ON DELETE SET NULL,
              ADD CONSTRAINT FK_439C02070574616 FOREIGN KEY (reviewer_id) REFERENCES Member (lidnr),
              ADD CONSTRAINT FK_439C020A19E445F FOREIGN KEY (lastEditedBy_id) REFERENCES User (lidnr),
              ADD CONSTRAINT FK_439C020102DD120 FOREIGN KEY (lastEditedByCompanyUser_id) REFERENCES CompanyUser (id) ON DELETE SET NULL
            SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE PollRevisionComment
              ADD CONSTRAINT FK_A6CF01781DFA7C8F FOREIGN KEY (revision_id) REFERENCES PollRevision (id),
              ADD CONSTRAINT FK_A6CF0178F675F31B FOREIGN KEY (author_id) REFERENCES User (lidnr),
              ADD CONSTRAINT FK_A6CF0178FD16CEE4 FOREIGN KEY (authorCompanyUser_id) REFERENCES CompanyUser (id) ON DELETE SET NULL
            SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE PollCommentReaction
              ADD CONSTRAINT FK_68E4C8D0F8697D13 FOREIGN KEY (comment_id) REFERENCES PollComment (id),
              ADD CONSTRAINT FK_68E4C8D0B44475EE FOREIGN KEY (member_lidnr) REFERENCES Member (lidnr)
            SQL);

        // The pointers the poll gains, and the closing date becoming something the board fills in rather than something
        // the question comes with.
        $this->addSql(<<<'SQL'
            ALTER TABLE Poll
              ADD currentRevision_id INT DEFAULT NULL,
              ADD liveRevision_id INT DEFAULT NULL,
              ADD votesAnonymisedAt DATETIME DEFAULT NULL,
              CHANGE expiryDate expiryDate DATE DEFAULT NULL
            SQL);

        // Every poll becomes its own first revision, carrying the question over as it stands. Whether the board ever
        // agreed to it is the only thing on record, so a poll nobody approved becomes a revision that was turned down;
        // there was never a way to leave one waiting. Each was put to the board at some point and none of them is a
        // draft, so they are all dated as submitted, by the only moment there is for them.
        $this->addSql(<<<'SQL'
            INSERT INTO PollRevision (
              poll_id, question_id, author_id, reviewer_id,
              status, revisionNumber, version, reviewedAt, submittedAt,
              createdAt, updatedAt
            )
            SELECT
              poll.id,
              poll.question_id,
              poll.creator_id,
              poll.approver_id,
              CASE WHEN poll.approver_id IS NULL THEN 'rejected' ELSE 'approved' END,
              1,
              1,
              CASE WHEN poll.approver_id IS NULL THEN NULL ELSE NOW() END,
              NOW(),
              NOW(),
              NOW()
            FROM Poll poll
            SQL);

        $this->addSql(<<<'SQL'
            UPDATE Poll poll
            JOIN PollRevision revision ON revision.poll_id = poll.id
            SET poll.currentRevision_id = revision.id,
                poll.liveRevision_id = CASE WHEN poll.approver_id IS NULL THEN NULL ELSE revision.id END
            SQL);

        // The answers move onto the revision the question was written in, so an approved question can never be given
        // options it was not approved with.
        $this->addSql(<<<'SQL'
            ALTER TABLE PollOption ADD revision_id INT DEFAULT NULL
            SQL);
        $this->addSql(<<<'SQL'
            UPDATE PollOption pollOption
            JOIN PollRevision revision ON revision.poll_id = pollOption.poll_id
            SET pollOption.revision_id = revision.id
            SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE PollOption DROP FOREIGN KEY `FK_FEFE970B3C947C0F`
            SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_FEFE970B3C947C0F ON PollOption
            SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE PollOption
              DROP poll_id,
              MODIFY revision_id INT NOT NULL
            SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE PollOption
              ADD CONSTRAINT FK_FEFE970B1DFA7C8F FOREIGN KEY (revision_id) REFERENCES PollRevision (id)
            SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_FEFE970B1DFA7C8F ON PollOption (revision_id)
            SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE Poll DROP FOREIGN KEY `FK_248E557B1E27F6BF`
            SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE Poll DROP FOREIGN KEY `FK_248E557BBB23766C`
            SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX UNIQ_248E557B1E27F6BF ON Poll
            SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_248E557BBB23766C ON Poll
            SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE Poll
              DROP question_id,
              DROP approver_id
            SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE Poll
              ADD CONSTRAINT FK_248E557B2796CA52 FOREIGN KEY (currentRevision_id) REFERENCES PollRevision (id),
              ADD CONSTRAINT FK_248E557BA892657C FOREIGN KEY (liveRevision_id) REFERENCES PollRevision (id)
            SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_248E557B2796CA52 ON Poll (currentRevision_id)
            SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_248E557BA892657C ON Poll (liveRevision_id)
            SQL);

        // A reply is filed under the comment it answers.
        $this->addSql(<<<'SQL'
            ALTER TABLE PollComment ADD parent_id INT DEFAULT NULL
            SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE PollComment
              ADD CONSTRAINT FK_C86340FF727ACA70 FOREIGN KEY (parent_id) REFERENCES PollComment (id)
            SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_C86340FF727ACA70 ON PollComment (parent_id)
            SQL);

        // The revision tables that were already there get what a poll's own is created with above. Anything past the
        // draft stage was submitted at some point, so it is dated by the only moment there is for it; a draft has not
        // been, and keeps nothing.
        foreach (self::REVISION_TABLES as $table) {
            $this->addSql('ALTER TABLE ' . $table . ' ADD submittedAt DATETIME DEFAULT NULL');
            $this->addSql('UPDATE ' . $table . ' SET submittedAt = createdAt WHERE status <> \'draft\'');
        }
    }

    public function down(Schema $schema): void
    {
        // The question and who agreed to it come back off whichever revision was live, or off the working head when
        // nothing was. The rest of the chain, the review discussion, the reactions and the replies do not survive
        // going back, and neither do answers belonging to a revision that was never live.
        $this->addSql(<<<'SQL'
            ALTER TABLE Poll
              ADD question_id INT DEFAULT NULL,
              ADD approver_id INT DEFAULT NULL
            SQL);
        $this->addSql(<<<'SQL'
            UPDATE Poll poll
            JOIN PollRevision revision ON revision.id = COALESCE(poll.liveRevision_id, poll.currentRevision_id)
            SET poll.question_id = revision.question_id,
                poll.approver_id = revision.reviewer_id
            SQL);
        $this->addSql(<<<'SQL'
            UPDATE Poll SET expiryDate = CURRENT_DATE() WHERE expiryDate IS NULL
            SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE PollOption ADD poll_id INT DEFAULT NULL
            SQL);
        $this->addSql(<<<'SQL'
            UPDATE PollOption pollOption
            JOIN PollRevision revision ON revision.id = pollOption.revision_id
            JOIN Poll poll ON poll.id = revision.poll_id
             AND revision.id = COALESCE(poll.liveRevision_id, poll.currentRevision_id)
            SET pollOption.poll_id = poll.id
            SQL);
        $this->addSql(<<<'SQL'
            DELETE FROM PollVote
            WHERE option_id IN (SELECT id FROM PollOption WHERE poll_id IS NULL)
            SQL);
        $this->addSql(<<<'SQL'
            DELETE FROM PollOption WHERE poll_id IS NULL
            SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE PollOption DROP FOREIGN KEY `FK_FEFE970B1DFA7C8F`
            SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_FEFE970B1DFA7C8F ON PollOption
            SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE PollOption
              DROP revision_id,
              MODIFY poll_id INT NOT NULL
            SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE PollOption
              ADD CONSTRAINT FK_FEFE970B3C947C0F FOREIGN KEY (poll_id) REFERENCES Poll (id)
            SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_FEFE970B3C947C0F ON PollOption (poll_id)
            SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE Poll
              DROP FOREIGN KEY `FK_248E557B2796CA52`,
              DROP FOREIGN KEY `FK_248E557BA892657C`
            SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_248E557B2796CA52 ON Poll
            SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_248E557BA892657C ON Poll
            SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE Poll
              DROP currentRevision_id,
              DROP liveRevision_id,
              DROP votesAnonymisedAt,
              MODIFY question_id INT NOT NULL,
              CHANGE expiryDate expiryDate DATE NOT NULL
            SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE Poll
              ADD CONSTRAINT FK_248E557B1E27F6BF FOREIGN KEY (question_id) REFERENCES FrontpageLocalisedText (id),
              ADD CONSTRAINT FK_248E557BBB23766C FOREIGN KEY (approver_id) REFERENCES Member (lidnr)
            SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_248E557B1E27F6BF ON Poll (question_id)
            SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_248E557BBB23766C ON Poll (approver_id)
            SQL);

        $this->addSql(<<<'SQL'
            DELETE FROM PollComment WHERE parent_id IS NOT NULL
            SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE PollComment DROP FOREIGN KEY `FK_C86340FF727ACA70`
            SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_C86340FF727ACA70 ON PollComment
            SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE PollComment DROP parent_id
            SQL);

        $this->addSql(<<<'SQL'
            DROP TABLE PollCommentReaction
            SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE PollRevisionComment
            SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE PollRevision
            SQL);

        foreach (self::REVISION_TABLES as $table) {
            $this->addSql('ALTER TABLE ' . $table . ' DROP submittedAt');
        }
    }
}
