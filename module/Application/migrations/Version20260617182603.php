<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * phpcs:disable Generic.Files.LineLength.TooLong
 * phpcs:disable SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall
 */
final class Version20260617182603 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Update member model to remove paid field from GEWISDB.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE Member DROP paid');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE Member ADD paid INT NOT NULL');
    }
}
