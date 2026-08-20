<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * phpcs:disable Generic.Files.LineLength.TooLong
 * phpcs:disable SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall
 */
final class Version20260815120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rebuild the option calendar, where bodies claim a date before they plan an activity on it. How many activities a body could put forward used to be written down per body the moment a period was opened, and every one of those rows started at zero, so a body founded after that had no row at all and was quietly told it could propose nothing. Nothing is written down per body any more: a number is worked out when it is asked for, from the association default, what the board set for the period, and any exception the board wrote for that one body, so a body nobody has ever heard of gets the ordinary allowance. A proposal now says which period it belongs to instead of that being guessed from when it was created, the date it holds is a real link rather than a word in a column, and it carries the activity it became. What is still to come is brought across: periods that have not finished, the proposals in them that still have a date ahead of them, and their dates. Limits are only brought across where the board raised them above zero, because the zeroes were written by the old code rather than by anyone. Proposals put forward by somebody who is neither a body nor the board are left behind, as there is nowhere to put them, and when a date was approved the fact is kept but not the day it was decided, which was never recorded.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE OptionPeriod (
              id INT AUTO_INCREMENT NOT NULL,
              name VARCHAR(128) NOT NULL,
              submissionOpensAt DATETIME NOT NULL,
              submissionClosesAt DATETIME NOT NULL,
              startsAt DATE NOT NULL,
              endsAt DATE NOT NULL,
              defaultMaxProposals INT DEFAULT NULL,
              createdAt DATETIME NOT NULL,
              updatedAt DATETIME NOT NULL,
              INDEX option_period_submission_window (submissionOpensAt, submissionClosesAt),
              INDEX option_period_starts_at (startsAt),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
            SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE ProposalLimit (
              id INT AUTO_INCREMENT NOT NULL,
              organ_id INT NOT NULL,
              maxProposals INT NOT NULL,
              UNIQUE INDEX proposal_limit_organ_uniq (organ_id),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
            SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE PeriodProposalLimit (
              id INT AUTO_INCREMENT NOT NULL,
              period_id INT NOT NULL,
              organ_id INT NOT NULL,
              maxProposals INT NOT NULL,
              INDEX IDX_2F1F4328EC8B7ADE (period_id),
              INDEX IDX_2F1F4328E4445171 (organ_id),
              UNIQUE INDEX period_proposal_limit_period_organ_uniq (period_id, organ_id),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
            SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE ActivityProposal (
              id INT AUTO_INCREMENT NOT NULL,
              period_id INT NOT NULL,
              organ_id INT DEFAULT NULL,
              createdBy_id INT NOT NULL,
              chosenOption_id INT DEFAULT NULL,
              activity_id INT DEFAULT NULL,
              decidedBy_id INT DEFAULT NULL,
              budgetClearedBy_id INT DEFAULT NULL,
              name VARCHAR(128) NOT NULL,
              description LONGTEXT DEFAULT NULL,
              status VARCHAR(32) NOT NULL,
              decidedAt DATETIME DEFAULT NULL,
              budgetClearance VARCHAR(32) DEFAULT NULL,
              budgetClearedAt DATETIME DEFAULT NULL,
              budgetRemindedAt DATETIME DEFAULT NULL,
              createdAt DATETIME NOT NULL,
              updatedAt DATETIME NOT NULL,
              INDEX IDX_25B61AC4EC8B7ADE (period_id),
              INDEX IDX_25B61AC4E4445171 (organ_id),
              INDEX IDX_25B61AC43174800F (createdBy_id),
              UNIQUE INDEX UNIQ_25B61AC46CD264D6 (chosenOption_id),
              UNIQUE INDEX UNIQ_25B61AC481C06096 (activity_id),
              INDEX IDX_25B61AC485C5BBC (decidedBy_id),
              INDEX IDX_25B61AC43B1D491C (budgetClearedBy_id),
              INDEX activity_proposal_period_organ (period_id, organ_id),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
            SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE ActivityDateOption (
              id INT AUTO_INCREMENT NOT NULL,
              proposal_id INT NOT NULL,
              decidedBy_id INT DEFAULT NULL,
              beginsAt DATE NOT NULL,
              endsAt DATE NOT NULL,
              timeOfDay VARCHAR(32) NOT NULL,
              position SMALLINT NOT NULL,
              status VARCHAR(32) NOT NULL,
              decidedAt DATETIME DEFAULT NULL,
              INDEX IDX_D57D3CDCF4792058 (proposal_id),
              INDEX IDX_D57D3CDC85C5BBC (decidedBy_id),
              INDEX activity_date_option_span (beginsAt, endsAt),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
            SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE ProposalLimit
              ADD CONSTRAINT FK_706DE9F1E4445171 FOREIGN KEY (organ_id) REFERENCES Organ (id)
            SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE PeriodProposalLimit
              ADD CONSTRAINT FK_2F1F4328EC8B7ADE FOREIGN KEY (period_id) REFERENCES OptionPeriod (id),
              ADD CONSTRAINT FK_2F1F4328E4445171 FOREIGN KEY (organ_id) REFERENCES Organ (id)
            SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ActivityProposal
              ADD CONSTRAINT FK_25B61AC4EC8B7ADE FOREIGN KEY (period_id) REFERENCES OptionPeriod (id),
              ADD CONSTRAINT FK_25B61AC4E4445171 FOREIGN KEY (organ_id) REFERENCES Organ (id),
              ADD CONSTRAINT FK_25B61AC43174800F FOREIGN KEY (createdBy_id) REFERENCES Member (lidnr),
              ADD CONSTRAINT FK_25B61AC46CD264D6 FOREIGN KEY (chosenOption_id) REFERENCES ActivityDateOption (id),
              ADD CONSTRAINT FK_25B61AC481C06096 FOREIGN KEY (activity_id) REFERENCES Activity (id) ON DELETE SET NULL,
              ADD CONSTRAINT FK_25B61AC485C5BBC FOREIGN KEY (decidedBy_id) REFERENCES Member (lidnr),
              ADD CONSTRAINT FK_25B61AC43B1D491C FOREIGN KEY (budgetClearedBy_id) REFERENCES Member (lidnr)
            SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ActivityDateOption
              ADD CONSTRAINT FK_D57D3CDCF4792058 FOREIGN KEY (proposal_id) REFERENCES ActivityProposal (id),
              ADD CONSTRAINT FK_D57D3CDC85C5BBC FOREIGN KEY (decidedBy_id) REFERENCES Member (lidnr)
            SQL);

        // Keep the old primary keys, so everything that pointed at a row still points at the same one and the copies
        // below can be written in one pass each.
        $this->addSql(<<<'SQL'
            INSERT INTO OptionPeriod (id, name, submissionOpensAt, submissionClosesAt, startsAt, endsAt, defaultMaxProposals, createdAt, updatedAt)
            SELECT c.id,
                   CONCAT('Option period ', DATE_FORMAT(c.beginOptionTime, '%d-%m-%Y'), ' - ', DATE_FORMAT(c.endOptionTime, '%d-%m-%Y')),
                   c.beginPlanningTime,
                   c.endPlanningTime,
                   DATE(c.beginOptionTime),
                   DATE(c.endOptionTime),
                   NULL,
                   NOW(),
                   NOW()
            FROM ActivityOptionCreationPeriod c
            WHERE c.endOptionTime >= NOW()
            SQL);

        // Only where the board raised the number above zero. A zero is indistinguishable from the row the old code
        // wrote for every body when the period was opened, and carrying those over would carry the bug over with them.
        $this->addSql(<<<'SQL'
            INSERT INTO PeriodProposalLimit (id, period_id, organ_id, maxProposals)
            SELECT m.id, m.period_id, m.organ_id, m.value
            FROM MaxActivities m
            INNER JOIN OptionPeriod p ON p.id = m.period_id
            WHERE m.value > 0
            SQL);

        // A proposal never said which period it was for, so the only thing left to go on is that it was created while
        // that period was taking proposals. Proposals whose dates have all been and gone are left behind, as is
        // anything put forward by neither a body nor the board.
        $this->addSql(<<<'SQL'
            INSERT INTO ActivityProposal (id, period_id, organ_id, createdBy_id, name, description, status, createdAt, updatedAt)
            SELECT o.id,
                   (SELECT p.id
                      FROM OptionPeriod p
                     WHERE o.creationTime BETWEEN p.submissionOpensAt AND p.submissionClosesAt
                     ORDER BY p.id ASC
                     LIMIT 1),
                   o.organ_id,
                   o.creator_id,
                   LEFT(o.name, 128),
                   NULLIF(o.description, ''),
                   'submitted',
                   o.creationTime,
                   NOW()
            FROM ActivityOptionProposal o
            WHERE (o.organ_id IS NOT NULL OR o.organAlt = 'Board')
              AND EXISTS (
                    SELECT 1 FROM OptionPeriod p
                     WHERE o.creationTime BETWEEN p.submissionOpensAt AND p.submissionClosesAt
                  )
              AND EXISTS (
                    SELECT 1 FROM ActivityCalendarOption a
                     WHERE a.proposal_id = o.id
                       AND a.beginTime >= NOW()
                       AND (a.status IS NULL OR a.status = 'approved')
                  )
            SQL);

        // Dates the body took back are not worth keeping, and three is the house limit, so anything beyond the first
        // three is dropped rather than saved into a shape that is already over the line.
        $this->addSql(<<<'SQL'
            INSERT INTO ActivityDateOption (id, proposal_id, decidedBy_id, beginsAt, endsAt, timeOfDay, position, status)
            SELECT o.id,
                   o.proposal_id,
                   CASE WHEN o.status = 'approved' THEN o.modifiedBy_id ELSE NULL END,
                   DATE(o.beginTime),
                   DATE(o.endTime),
                   CASE o.type
                     WHEN 'Morning' THEN 'morning'
                     WHEN 'Lunch break' THEN 'lunch-break'
                     WHEN 'Afternoon' THEN 'afternoon'
                     WHEN 'Evening' THEN 'evening'
                     WHEN 'Multiple days' THEN 'multiple-days'
                     ELSE 'day'
                   END,
                   o.rank_in_proposal,
                   CASE WHEN o.status = 'approved' THEN 'approved' ELSE 'proposed' END
            FROM (
                SELECT a.*,
                       ROW_NUMBER() OVER (PARTITION BY a.proposal_id ORDER BY a.beginTime ASC, a.id ASC) AS rank_in_proposal
                  FROM ActivityCalendarOption a
                 WHERE a.proposal_id IN (SELECT id FROM ActivityProposal)
                   AND (a.status IS NULL OR a.status = 'approved')
            ) o
            WHERE o.rank_in_proposal <= 3
            SQL);

        // The date a proposal holds becomes a link rather than a word in a column. When it was decided was never
        // written down, so it stays empty; who decided it was, and is kept.
        $this->addSql(<<<'SQL'
            UPDATE ActivityProposal p
            INNER JOIN ActivityDateOption o ON o.proposal_id = p.id AND o.status = 'approved'
               SET p.chosenOption_id = o.id,
                   p.status = 'scheduled',
                   p.decidedBy_id = o.decidedBy_id
            SQL);

        // Nothing outside these four points at them, and a table takes its own foreign keys with it, so dropping the
        // children before the parents is all the ordering that is needed.
        $this->addSql(<<<'SQL'
            DROP TABLE ActivityCalendarOption
            SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE MaxActivities
            SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE ActivityOptionProposal
            SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE ActivityOptionCreationPeriod
            SQL);
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException(
            'The zeroes the old option calendar wrote for every body are deliberately not put back, and what became '
            . 'of a proposal that was neither a body\'s nor the board\'s is not kept, so the old tables cannot be '
            . 'filled in again.',
        );
    }
}
