<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250114092913 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add presence to Signup and SignupList (see GH-1921)';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE Signup ADD present TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE SignupList ADD presenceTaken TINYINT(1) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE Signup DROP present');
        $this->addSql('ALTER TABLE SignupList DROP presenceTaken');
    }
}
