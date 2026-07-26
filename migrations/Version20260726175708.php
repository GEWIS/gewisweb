<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * phpcs:disable Generic.Files.LineLength.TooLong
 * phpcs:disable SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall
 */
final class Version20260726175708 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add the Announcement table for sticky site announcement banners.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE Announcement (level VARCHAR(16) NOT NULL, endsAt DATETIME NOT NULL, createdAt DATETIME NOT NULL, id INT AUTO_INCREMENT NOT NULL, title_id INT NOT NULL, body_id INT NOT NULL, UNIQUE INDEX UNIQ_558802E4A9F87BD (title_id), UNIQUE INDEX UNIQ_558802E49B621D84 (body_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE Announcement ADD CONSTRAINT FK_558802E4A9F87BD FOREIGN KEY (title_id) REFERENCES ApplicationLocalisedText (id)');
        $this->addSql('ALTER TABLE Announcement ADD CONSTRAINT FK_558802E49B621D84 FOREIGN KEY (body_id) REFERENCES ApplicationLocalisedText (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE Announcement DROP FOREIGN KEY FK_558802E4A9F87BD');
        $this->addSql('ALTER TABLE Announcement DROP FOREIGN KEY FK_558802E49B621D84');
        $this->addSql('DROP TABLE Announcement');
    }
}
