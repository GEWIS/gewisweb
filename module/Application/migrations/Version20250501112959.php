<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * phpcs:disable Generic.Files.LineLength.TooLong
 * phpcs:disable SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall
 */
final class Version20250501112959 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Allow signup lists to be promoted over other signup lists.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE SignupList ADD promoted TINYINT(1) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE SignupList DROP promoted');
    }
}
