<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * phpcs:disable Generic.Files.LineLength.TooLong
 * phpcs:disable SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall
 */
final class Version20260811094722 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Let a company say where else it can be followed. Only the handle is kept, never the link it was pasted from, so the address is rebuilt without whatever the app it was copied from wanted to know about the reader. The links belong to a revision, so adding or dropping one is reviewed like the rest of the profile.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE CompanySocialLink (
              id INT AUTO_INCREMENT NOT NULL,
              revision_id INT NOT NULL,
              platform VARCHAR(255) NOT NULL,
              handle VARCHAR(255) NOT NULL,
              INDEX IDX_AAD03511DFA7C8F (revision_id),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
            SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE CompanySocialLink
              ADD CONSTRAINT FK_AAD03511DFA7C8F FOREIGN KEY (revision_id) REFERENCES CompanyRevision (id)
            SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            DROP TABLE CompanySocialLink
            SQL);
    }
}
