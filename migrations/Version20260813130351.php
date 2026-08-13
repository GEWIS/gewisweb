<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * phpcs:disable Generic.Files.LineLength.TooLong
 * phpcs:disable SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall
 */
final class Version20260813130351 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Say what a piece of news is about, so the feed can be narrowed to the part of the association a reader cares about. Nothing written so far said, so all of it becomes association news, which is what a piece of news is when it is about the association as a whole.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE NewsItem ADD category VARCHAR(255) DEFAULT NULL
            SQL);
        $this->addSql(<<<'SQL'
            UPDATE NewsItem SET category = 'association'
            SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE NewsItem MODIFY category VARCHAR(255) NOT NULL
            SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE NewsItem DROP category
            SQL);
    }
}
