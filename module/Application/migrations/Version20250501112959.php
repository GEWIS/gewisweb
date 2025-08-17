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
final class Version20250501112959 extends AbstractMigration
{
    #[Override]
    public function getDescription(): string
    {
        return 'Allow signup lists to be promoted over other signup lists.';
    }

    #[Override]
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE SignupList ADD promoted TINYINT(1) NOT NULL');
    }

    #[Override]
    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE SignupList DROP promoted');
    }
}
