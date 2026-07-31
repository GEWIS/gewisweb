<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * phpcs:disable Generic.Files.LineLength.TooLong
 * phpcs:disable SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall
 */
final class Version20260729171543 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Allow a notification to be addressed to a single account, and to keep the parts it needs to say what '
            . 'it is about when it has no subject to point at.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE Notification ADD context JSON DEFAULT NULL, ADD recipientUser INT DEFAULT NULL, ADD recipientCompanyUser INT DEFAULT NULL');
        $this->addSql('ALTER TABLE Notification ADD CONSTRAINT FK_A765AD32DE12AB56 FOREIGN KEY (recipientUser) REFERENCES User (lidnr) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE Notification ADD CONSTRAINT FK_A765AD32F9EED134 FOREIGN KEY (recipientCompanyUser) REFERENCES CompanyUser (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX notification_recipient_user ON Notification (recipientUser, createdAt)');
        $this->addSql('CREATE INDEX IDX_A765AD32F9EED134 ON Notification (recipientCompanyUser)');
        $this->addSql('CREATE INDEX notification_created_at ON Notification (createdAt)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE Notification DROP FOREIGN KEY FK_A765AD32DE12AB56');
        $this->addSql('ALTER TABLE Notification DROP FOREIGN KEY FK_A765AD32F9EED134');
        $this->addSql('DROP INDEX notification_created_at ON Notification');
        $this->addSql('DROP INDEX IDX_A765AD32F9EED134 ON Notification');
        $this->addSql('DROP INDEX notification_recipient_user ON Notification');
        $this->addSql('ALTER TABLE Notification DROP context, DROP recipientUser, DROP recipientCompanyUser');
    }
}
