<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * phpcs:disable Generic.Files.LineLength.TooLong
 * phpcs:disable SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall
 */
final class Version20260731122949 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remember when the members on a sign-up list were told it was about to close, so they are told once.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE SignupList ADD remindedAt DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE SignupList DROP remindedAt');
    }
}
