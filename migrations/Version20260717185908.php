<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * phpcs:disable Generic.Files.LineLength.TooLong
 * phpcs:disable SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall
 */
final class Version20260717185908 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Let external applications be disabled or given an expiry date so old ones can no longer authenticate.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ExternalApp ADD enabled TINYINT DEFAULT 1 NOT NULL, ADD expiresAt DATETIME DEFAULT NULL');
        // The earlier table rename left the authentication log's indexes under their old, hashed names.
        $this->addSql('ALTER TABLE ExternalAppAuthentication RENAME INDEX idx_d9fd7eb6a76ed395 TO IDX_2AF91860A76ED395');
        $this->addSql('ALTER TABLE ExternalAppAuthentication RENAME INDEX idx_d9fd7eb67987212d TO IDX_2AF918607987212D');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ExternalApp DROP enabled, DROP expiresAt');
        $this->addSql('ALTER TABLE ExternalAppAuthentication RENAME INDEX idx_2af918607987212d TO IDX_D9FD7EB67987212D');
        $this->addSql('ALTER TABLE ExternalAppAuthentication RENAME INDEX idx_2af91860a76ed395 TO IDX_D9FD7EB6A76ED395');
    }
}
