<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * phpcs:disable Generic.Files.LineLength.TooLong
 * phpcs:disable SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall
 */
final class Version20260731115013 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Let a notification be addressed to whoever holds a role, for work a group is responsible for rather '
            . 'than one account.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE Notification ADD recipientRole VARCHAR(32) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE Notification DROP recipientRole');
    }
}
