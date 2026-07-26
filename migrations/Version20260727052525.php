<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * phpcs:disable Generic.Files.LineLength.TooLong
 * phpcs:disable SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall
 */
final class Version20260727052525 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add the MaintenanceWindow table for scheduling app-level maintenance and read-only mode.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE MaintenanceWindow (status VARCHAR(16) NOT NULL, startsAt DATETIME DEFAULT NULL, endsAt DATETIME DEFAULT NULL, id INT AUTO_INCREMENT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE MaintenanceWindow');
    }
}
