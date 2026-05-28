<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * phpcs:disable Generic.Files.LineLength.TooLong
 * phpcs:disable SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall
 */
final class Version20260528095542 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add a mandatory category to activities, replacing isMyFuture (now the career category); GH-2052.';
    }

    public function up(Schema $schema): void
    {
        // Add as nullable first so existing rows remain valid, then backfill and enforce NOT NULL.
        $this->addSql('ALTER TABLE Activity ADD category VARCHAR(255) DEFAULT NULL');
        // The career category replaces isMyFuture (decided with the external affairs officer).
        $this->addSql('UPDATE Activity SET category = \'career\' WHERE isMyFuture = 1');
        // Existing activities have no category; mark them uncategorised (not selectable for new activities).
        $this->addSql('UPDATE Activity SET category = \'uncategorised\' WHERE category IS NULL');
        $this->addSql('ALTER TABLE Activity CHANGE category category VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE Activity DROP isMyFuture');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE Activity ADD isMyFuture TINYINT(1) DEFAULT NULL');
        $this->addSql('UPDATE Activity SET isMyFuture = 1 WHERE category = \'career\'');
        $this->addSql('UPDATE Activity SET isMyFuture = 0 WHERE isMyFuture IS NULL');
        $this->addSql('ALTER TABLE Activity CHANGE isMyFuture isMyFuture TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE Activity DROP category');
    }
}
