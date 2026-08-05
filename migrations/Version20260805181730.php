<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * phpcs:disable Generic.Files.LineLength.TooLong
 * phpcs:disable SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall
 */
final class Version20260805181730 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Record every requested download of a course document. The watermark burnt into a download names who asked for it and when, so the request has to exist before the file does; the row is also the handle the browser waits on while a worker builds it, and the reference that lets a copy found elsewhere be traced back.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE CourseDocumentDownload (
              id INT AUTO_INCREMENT NOT NULL,
              document_id INT NOT NULL,
              requested_by INT DEFAULT NULL,
              token BINARY(16) NOT NULL,
              requestedByName VARCHAR(255) NOT NULL,
              requestedFrom VARCHAR(255) NOT NULL,
              requestedAt DATETIME NOT NULL,
              collectedAt DATETIME DEFAULT NULL,
              status VARCHAR(255) NOT NULL,
              path VARCHAR(255) DEFAULT NULL,
              UNIQUE INDEX UNIQ_927B91875F37A13B (token),
              INDEX IDX_927B9187C33F7837 (document_id),
              INDEX IDX_927B918718C491A5 (requested_by),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);

        // A download belongs to the document it was made from, so it goes when that does. It survives the account that
        // asked for it being removed, because the name it burnt into the file is already a copy.
        $this->addSql('ALTER TABLE CourseDocumentDownload ADD CONSTRAINT FK_927B9187C33F7837 FOREIGN KEY (document_id) REFERENCES CourseDocument (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE CourseDocumentDownload ADD CONSTRAINT FK_927B918718C491A5 FOREIGN KEY (requested_by) REFERENCES User (lidnr) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE CourseDocumentDownload DROP FOREIGN KEY FK_927B9187C33F7837');
        $this->addSql('ALTER TABLE CourseDocumentDownload DROP FOREIGN KEY FK_927B918718C491A5');
        $this->addSql('DROP TABLE CourseDocumentDownload');
    }
}
