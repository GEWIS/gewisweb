<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * phpcs:disable Generic.Files.LineLength.TooLong
 * phpcs:disable SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall
 */
final class Version20260731094517 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Record what a member has done with a single notification, so one can be read or cleared away without '
            . 'touching it for anybody else.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE NotificationInteraction (readAt DATETIME DEFAULT NULL, dismissedAt DATETIME DEFAULT NULL, id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, notification_id INT NOT NULL, INDEX IDX_2BCD2BB2A76ED395 (user_id), INDEX IDX_2BCD2BB2EF1A9D84 (notification_id), UNIQUE INDEX UNIQ_2BCD2BB2A76ED395EF1A9D84 (user_id, notification_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE NotificationInteraction ADD CONSTRAINT FK_2BCD2BB2A76ED395 FOREIGN KEY (user_id) REFERENCES User (lidnr) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE NotificationInteraction ADD CONSTRAINT FK_2BCD2BB2EF1A9D84 FOREIGN KEY (notification_id) REFERENCES Notification (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE NotificationInteraction DROP FOREIGN KEY FK_2BCD2BB2A76ED395');
        $this->addSql('ALTER TABLE NotificationInteraction DROP FOREIGN KEY FK_2BCD2BB2EF1A9D84');
        $this->addSql('DROP TABLE NotificationInteraction');
    }
}
