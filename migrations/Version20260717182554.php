<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260717182554 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename the ApiApp tables to ExternalApp; the login flow is unrelated to the REST API.';
    }

    public function up(Schema $schema): void
    {
        // A single RENAME TABLE statement is atomic and carries the foreign key from the authentication log over to the
        // renamed table.
        $this->addSql('RENAME TABLE ApiApp TO ExternalApp, ApiAppAuthentication TO ExternalAppAuthentication');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('RENAME TABLE ExternalApp TO ApiApp, ExternalAppAuthentication TO ApiAppAuthentication');
    }
}
