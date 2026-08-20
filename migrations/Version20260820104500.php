<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * phpcs:disable Generic.Files.LineLength.TooLong
 * phpcs:disable SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall
 */
final class Version20260820104500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Give a notification room to say what kind it is. The column held 32 characters, which three kinds are already longer than: telling reviewers that a body has put its page forward runs to 42. Writing one of those failed outright, so the first body to submit its page for review would have been met with a database error rather than a notification going out. Sixty-four leaves room for the kinds there are and for a few more.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE Notification MODIFY type VARCHAR(64) NOT NULL
            SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE Notification MODIFY type VARCHAR(32) NOT NULL
            SQL);
    }
}
