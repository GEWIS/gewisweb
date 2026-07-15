<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * phpcs:disable Generic.Files.LineLength.TooLong
 * phpcs:disable SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall
 */
final class Version20260715140922 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add a display position to sign-up fields and options (reorderable in the editor) and a default marker to choice options; GH-2036, GH-2106.';
    }

    public function up(Schema $schema): void
    {
        // GH-2036: the organiser reorders the questions and (choice) options by dragging; a 0-based position (lower
        // first) fixes the order. Existing rows default to 0 and keep their relative order via the id tiebreaker.
        $this->addSql('ALTER TABLE SignupField ADD position INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE SignupOption ADD position INT DEFAULT 0 NOT NULL');

        // GH-2106: a choice option can be marked as the default preselected value. Add it like the other booleans:
        // nullable first so existing rows can be backfilled to false, then make it mandatory.
        $this->addSql('ALTER TABLE SignupOption ADD isDefault TINYINT DEFAULT NULL');
        $this->addSql('UPDATE SignupOption SET isDefault = 0');
        $this->addSql('ALTER TABLE SignupOption CHANGE isDefault isDefault TINYINT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE SignupField DROP position');
        $this->addSql('ALTER TABLE SignupOption DROP position, DROP isDefault');
    }
}
