<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * phpcs:disable Generic.Files.LineLength.TooLong
 * phpcs:disable SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall
 */
final class Version20260512100037 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Update foundation subdecision to have purpose field from GEWISDB.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE SubDecision ADD purpose VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE SubDecision DROP purpose');
    }
}
