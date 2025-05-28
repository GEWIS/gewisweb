<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Override;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250218153504 extends AbstractMigration
{
    #[Override]
    public function getDescription(): string
    {
        return 'Add draw status to Signup modal';
    }

    #[Override]
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE Signup ADD drawn TINYINT(1) DEFAULT 1 NOT NULL');
    }

    #[Override]
    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE Signup DROP drawn');
    }
}
