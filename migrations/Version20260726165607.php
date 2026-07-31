<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * phpcs:disable Generic.Files.LineLength.TooLong
 * phpcs:disable SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall
 */
final class Version20260726165607 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add the notification centre: a Notification table and a per-member read marker on UserSettings.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE ApplicationLocalisedText (valueEN LONGTEXT DEFAULT NULL, valueNL LONGTEXT DEFAULT NULL, id INT AUTO_INCREMENT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE Notification (type VARCHAR(32) NOT NULL, subjectId INT DEFAULT NULL, level VARCHAR(16) NOT NULL, createdAt DATETIME NOT NULL, id INT AUTO_INCREMENT NOT NULL, UNIQUE INDEX notification_type_subject (type, subjectId), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE UserSettings ADD notificationsReadAt DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE Notification');
        $this->addSql('DROP TABLE ApplicationLocalisedText');
        $this->addSql('ALTER TABLE UserSettings DROP notificationsReadAt');
    }
}
