<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * phpcs:disable Generic.Files.LineLength.TooLong
 * phpcs:disable SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall
 */
final class Version20260728175050 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add the per-category email opt-ins with their digest frequency, the pending-email queue, and the '
            . 'pause switch for notification emails.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE NotificationEmailSubscription (category VARCHAR(64) NOT NULL, frequency VARCHAR(16) DEFAULT \'immediately\' NOT NULL, lastSentAt DATETIME DEFAULT NULL, id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, INDEX IDX_280C6A9AA76ED395 (user_id), UNIQUE INDEX UNIQ_280C6A9AA76ED39564C19C1 (user_id, category), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE PendingNotificationEmail (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, notification_id INT NOT NULL, INDEX IDX_946F4830A76ED395 (user_id), INDEX IDX_946F4830EF1A9D84 (notification_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE NotificationEmailSubscription ADD CONSTRAINT FK_280C6A9AA76ED395 FOREIGN KEY (user_id) REFERENCES User (lidnr) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE PendingNotificationEmail ADD CONSTRAINT FK_946F4830A76ED395 FOREIGN KEY (user_id) REFERENCES User (lidnr) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE PendingNotificationEmail ADD CONSTRAINT FK_946F4830EF1A9D84 FOREIGN KEY (notification_id) REFERENCES Notification (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE UserSettings ADD notificationsPaused TINYINT DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE NotificationEmailSubscription DROP FOREIGN KEY FK_280C6A9AA76ED395');
        $this->addSql('ALTER TABLE PendingNotificationEmail DROP FOREIGN KEY FK_946F4830A76ED395');
        $this->addSql('ALTER TABLE PendingNotificationEmail DROP FOREIGN KEY FK_946F4830EF1A9D84');
        $this->addSql('DROP TABLE NotificationEmailSubscription');
        $this->addSql('DROP TABLE PendingNotificationEmail');
        $this->addSql('ALTER TABLE UserSettings DROP notificationsPaused');
    }
}
