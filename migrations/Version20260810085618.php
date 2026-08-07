<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * phpcs:disable Generic.Files.LineLength.TooLong
 * phpcs:disable SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall
 */
final class Version20260810085618 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ask a company for two logos rather than one. The square mark is what fits beside a line of text and the wider one, the mark and the name together, is what leads a company card; one image could never sit well in both. The single logo a company already handed over becomes its square one, and the banner is empty until it uploads one.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE CompanyRevision
              CHANGE logo squareLogo VARCHAR(255) DEFAULT NULL,
              ADD bannerLogo VARCHAR(255) DEFAULT NULL
            SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE CompanyRevision
              CHANGE squareLogo logo VARCHAR(255) DEFAULT NULL,
              DROP bannerLogo
            SQL);
    }
}
