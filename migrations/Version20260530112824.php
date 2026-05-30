<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Move sign-up lists from the Activity onto the ActivityRevision: each revision owns its own (cloned) lists, so list
 * changes are staged with the revision and only go live on approval. Adds a stable `lineageId` shared by every clone
 * of a logical list, used to migrate sign-ups across revisions on approval.
 *
 * phpcs:disable Generic.Files.LineLength.TooLong
 * phpcs:disable SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall
 */
final class Version20260530112824 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Move sign-up lists from the activity onto the activity revision.';
    }

    public function up(Schema $schema): void
    {
        // Add the new revision FK and the lineage id as nullable, backfill from the existing rows (a list moves to its
        // activity's live revision, else its current revision; the lineage id is a fresh UUID stored as 16 raw bytes),
        // then finalise to NOT NULL and swap the foreign key. Every activity has at least a current revision.
        $this->addSql('ALTER TABLE SignupList ADD activity_revision_id INT DEFAULT NULL, ADD lineageId BINARY(16) DEFAULT NULL');
        $this->addSql(<<<'SQL'
            UPDATE SignupList sl
            JOIN Activity a ON sl.activity_id = a.id
            SET sl.activity_revision_id = COALESCE(a.liveRevision_id, a.currentRevision_id),
                sl.lineageId = UNHEX(REPLACE(UUID(), '-', ''))
            SQL);
        $this->addSql('ALTER TABLE SignupList DROP FOREIGN KEY `FK_274D085F81C06096`');
        $this->addSql('DROP INDEX IDX_274D085F81C06096 ON SignupList');
        $this->addSql('ALTER TABLE SignupList DROP activity_id');
        $this->addSql('ALTER TABLE SignupList CHANGE activity_revision_id activity_revision_id INT NOT NULL');
        $this->addSql('ALTER TABLE SignupList CHANGE lineageId lineageId BINARY(16) NOT NULL');
        $this->addSql('ALTER TABLE SignupList ADD CONSTRAINT FK_274D085F13741683 FOREIGN KEY (activity_revision_id) REFERENCES ActivityRevision (id)');
        $this->addSql('CREATE INDEX IDX_274D085F13741683 ON SignupList (activity_revision_id)');
        // A revision never holds two lists of the same lineage.
        $this->addSql('CREATE UNIQUE INDEX signup_list_revision_lineage_uniq ON SignupList (activity_revision_id, lineageId)');
    }

    public function down(Schema $schema): void
    {
        // Reattach each list to the activity its owning revision belongs to, then restore the original column/FK.
        // Best-effort (as with the prior revision-split migration's down()): an activity with several revisions holds
        // a clone of each list per revision, so all clones reattach to the same activity_id and the rolled-back legacy
        // schema ends up with duplicate lists per logical list. Deleting the extras here would orphan their child rows.
        $this->addSql('ALTER TABLE SignupList DROP FOREIGN KEY FK_274D085F13741683');
        $this->addSql('DROP INDEX IDX_274D085F13741683 ON SignupList');
        $this->addSql('DROP INDEX signup_list_revision_lineage_uniq ON SignupList');
        $this->addSql('ALTER TABLE SignupList ADD activity_id INT DEFAULT NULL');
        $this->addSql(<<<'SQL'
            UPDATE SignupList sl
            JOIN ActivityRevision r ON sl.activity_revision_id = r.id
            SET sl.activity_id = r.activity_id
            SQL);
        $this->addSql('ALTER TABLE SignupList DROP lineageId, DROP activity_revision_id');
        $this->addSql('ALTER TABLE SignupList CHANGE activity_id activity_id INT NOT NULL');
        $this->addSql('ALTER TABLE SignupList ADD CONSTRAINT `FK_274D085F81C06096` FOREIGN KEY (activity_id) REFERENCES Activity (id)');
        $this->addSql('CREATE INDEX IDX_274D085F81C06096 ON SignupList (activity_id)');
    }
}
