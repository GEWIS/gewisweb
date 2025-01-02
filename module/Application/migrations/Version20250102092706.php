<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * phpcs:disable Generic.Files.LineLength.TooLong
 * phpcs:disable SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall
 */
final class Version20250102092706 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Allow Photos of the Week to be hidden (see GH-1806).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE WeeklyPhoto ADD hidden TINYINT(1) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE WeeklyPhoto DROP hidden');
    }
}
