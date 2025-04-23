<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * phpcs:disable Generic.Files.LineLength.TooLong
 * phpcs:disable SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall
 */
final class Version20250423132841 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Update reference models from GEWISDB/ReportDB. Includes improved handling of installation functions.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE Decision ADD contentEN LONGTEXT NOT NULL');
        $this->addSql('ALTER TABLE Decision CHANGE content contentNL LONGTEXT NOT NULL');
        $this->addSql('ALTER TABLE SubDecision ADD contentEN LONGTEXT NOT NULL');
        $this->addSql('ALTER TABLE SubDecision CHANGE content contentNL LONGTEXT NOT NULL');
        $this->addSql('ALTER TABLE SubDecision ADD CONSTRAINT FK_F0D6EE40EFBA85FF292FAD51 FOREIGN KEY (r_meeting_type, r_meeting_number) REFERENCES Meeting (type, number)');
        $this->addSql('CREATE INDEX IDX_F0D6EE40EFBA85FF292FAD51 ON SubDecision (r_meeting_type, r_meeting_number)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE SubDecision DROP FOREIGN KEY FK_F0D6EE40EFBA85FF292FAD51');
        $this->addSql('DROP INDEX IDX_F0D6EE40EFBA85FF292FAD51 ON SubDecision');
        $this->addSql('ALTER TABLE SubDecision CHANGE contentNL content LONGTEXT NOT NULL');
        $this->addSql('ALTER TABLE SubDecision DROP contentEN');
        $this->addSql('ALTER TABLE Decision CHANGE contentNL content LONGTEXT NOT NULL');
        $this->addSql('ALTER TABLE Decision DROP contentEN');
    }
}
