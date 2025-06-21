<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Override;

/**
 * phpcs:disable Generic.Files.LineLength.TooLong
 * phpcs:disable SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall
 */
final class Version20250227142446 extends AbstractMigration
{
    #[Override]
    public function getDescription(): string
    {
        return 'Add Zettle as option for requestable facilities for an activity.';
    }

    #[Override]
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE Activity ADD requireZettle TINYINT(1) NOT NULL');
    }

    #[Override]
    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE Activity DROP requireZettle');
    }
}
