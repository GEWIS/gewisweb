<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * phpcs:disable Generic.Files.LineLength.TooLong
 * phpcs:disable SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall
 */
final class Version20260717194005 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Give external applications a signing algorithm and token delivery mode; the shared secret is now optional.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ExternalApp ADD signature VARCHAR(255) DEFAULT \'EdDSA\' NOT NULL, ADD tokenDelivery VARCHAR(255) DEFAULT \'fragment\' NOT NULL, CHANGE secret secret VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ExternalApp DROP signature, DROP tokenDelivery, CHANGE secret secret VARCHAR(255) NOT NULL');
    }
}
