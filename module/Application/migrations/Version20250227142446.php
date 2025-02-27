<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * phpcs:disable Generic.Files.LineLength.TooLong
 * phpcs:disable SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall
 */
final class Version20250227142446 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add Zettle as option for requestable facilities for an activity.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE Activity ADD requireZettle TINYINT(1) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE Activity DROP requireZettle');
    }
}
