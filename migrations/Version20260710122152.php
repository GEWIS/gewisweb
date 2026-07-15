<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * phpcs:disable Generic.Files.LineLength.TooLong
 * phpcs:disable SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall
 */
final class Version20260710122152 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Convert photo tags to single-table inheritance (member/organ) with optional point-in-image positions; GH-1991.';
    }

    public function up(Schema $schema): void
    {
        // Add the new columns. `dtype` starts nullable so the existing rows can be backfilled before it becomes
        // mandatory; `member_id` becomes nullable so organ tags (which have no member) can share the single table.
        $this->addSql('ALTER TABLE Tag ADD positionX DOUBLE PRECISION DEFAULT NULL, ADD positionY DOUBLE PRECISION DEFAULT NULL, ADD dtype VARCHAR(255) DEFAULT NULL, ADD organ_id INT DEFAULT NULL, CHANGE member_id member_id INT DEFAULT NULL');

        // Every tag that exists today identifies a member.
        $this->addSql('UPDATE Tag SET dtype = \'member\'');
        $this->addSql('ALTER TABLE Tag CHANGE dtype dtype VARCHAR(255) NOT NULL');

        // Organ-tag foreign key and its lookup index.
        $this->addSql('ALTER TABLE Tag ADD CONSTRAINT FK_3BC4F163E4445171 FOREIGN KEY (organ_id) REFERENCES Organ (id)');
        $this->addSql('CREATE INDEX IDX_3BC4F163E4445171 ON Tag (organ_id)');

        // Split the single member-scoped uniqueness into one unique index per subtype. MariaDB treats NULLs as
        // distinct, so a member row (NULL organ_id) and an organ row (NULL member_id) never collide. The old index
        // already covers exactly (photo_id, member_id), so rename it rather than rebuild it.
        $this->addSql('ALTER TABLE Tag RENAME INDEX tag_idx TO tag_member_uniq');
        $this->addSql('CREATE UNIQUE INDEX tag_organ_uniq ON Tag (photo_id, organ_id)');
    }

    public function down(Schema $schema): void
    {
        // Organ tags cannot be represented once organ_id is gone, and would violate the restored NOT NULL member_id,
        // so drop them first.
        $this->addSql('DELETE FROM Tag WHERE dtype = \'organ\'');

        $this->addSql('ALTER TABLE Tag DROP FOREIGN KEY FK_3BC4F163E4445171');
        $this->addSql('DROP INDEX IDX_3BC4F163E4445171 ON Tag');
        $this->addSql('DROP INDEX tag_organ_uniq ON Tag');
        $this->addSql('ALTER TABLE Tag RENAME INDEX tag_member_uniq TO tag_idx');
        $this->addSql('ALTER TABLE Tag DROP positionX, DROP positionY, DROP dtype, DROP organ_id, CHANGE member_id member_id INT NOT NULL');
    }
}
