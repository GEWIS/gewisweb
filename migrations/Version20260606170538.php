<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260606170538 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add the numeric capacity of a (limited-capacity) sign-up list.';
    }

    public function up(Schema $schema): void
    {
        // The maximum number of admitted sign-ups for a limited-capacity list; null when unlimited.
        $this->addSql('ALTER TABLE SignupList ADD capacity INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE SignupList DROP capacity');
    }
}
