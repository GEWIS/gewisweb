<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * phpcs:disable Generic.Files.LineLength.TooLong
 * phpcs:disable SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall
 */
final class Version20260715154920 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add the UserSettings table for per-member settings and privacy preferences.';
    }

    public function up(Schema $schema): void
    {
        // App-owned per-member preferences, keyed by `lidnr` (derived identity, shared PK with `User`). A missing row
        // means "all defaults", so no backfill is needed. Deliberately NOT on `Member`, which is synced from GEWISDB.
        $this->addSql('CREATE TABLE UserSettings (lidnr INT NOT NULL, disableCosmetics TINYINT(1) DEFAULT 0 NOT NULL, photoTaggingOptOut TINYINT(1) DEFAULT 0 NOT NULL, hideYearOfBirth TINYINT(1) DEFAULT 0 NOT NULL, hideBirthdayOnFrontpage TINYINT(1) DEFAULT 0 NOT NULL, PRIMARY KEY(lidnr)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE UserSettings ADD CONSTRAINT FK_UserSettings_User FOREIGN KEY (lidnr) REFERENCES User (lidnr) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE UserSettings DROP FOREIGN KEY FK_UserSettings_User');
        $this->addSql('DROP TABLE UserSettings');
    }
}
