<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add the ActivityRevisionEdit table: the append-only audit trail of in-place edits to an activity revision.
 *
 * phpcs:disable Generic.Files.LineLength.TooLong
 * phpcs:disable SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall
 */
final class Version20260602175007 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add the activity revision edit audit trail.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE ActivityRevisionEdit (editedAt DATETIME NOT NULL, changedFields JSON NOT NULL, id INT AUTO_INCREMENT NOT NULL, revision_id INT NOT NULL, editor_id INT NOT NULL, INDEX IDX_285C37811DFA7C8F (revision_id), INDEX IDX_285C37816995AC4C (editor_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE ActivityRevisionEdit ADD CONSTRAINT FK_285C37811DFA7C8F FOREIGN KEY (revision_id) REFERENCES ActivityRevision (id)');
        $this->addSql('ALTER TABLE ActivityRevisionEdit ADD CONSTRAINT FK_285C37816995AC4C FOREIGN KEY (editor_id) REFERENCES User (lidnr)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ActivityRevisionEdit DROP FOREIGN KEY FK_285C37811DFA7C8F');
        $this->addSql('ALTER TABLE ActivityRevisionEdit DROP FOREIGN KEY FK_285C37816995AC4C');
        $this->addSql('DROP TABLE ActivityRevisionEdit');
    }
}
