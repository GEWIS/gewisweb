<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * phpcs:disable Generic.Files.LineLength.TooLong
 * phpcs:disable SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall
 */
final class Version20260717214921 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Record member data export requests so a repeated request can be refused while one is still relevant.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE DataExportRequest (id INT AUTO_INCREMENT NOT NULL, requestedAt DATETIME NOT NULL, user_id INT NOT NULL, INDEX IDX_2E59BBF8A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE DataExportRequest ADD CONSTRAINT FK_2E59BBF8A76ED395 FOREIGN KEY (user_id) REFERENCES User (lidnr)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE DataExportRequest DROP FOREIGN KEY FK_2E59BBF8A76ED395');
        $this->addSql('DROP TABLE DataExportRequest');
    }
}
