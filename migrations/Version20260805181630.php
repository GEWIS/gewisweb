<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * phpcs:disable Generic.Files.LineLength.TooLong
 * phpcs:disable SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall
 */
final class Version20260805181630 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Give course documents the page images a download is rebuilt from. An exam is no longer handed out as the file that was uploaded: it is rasterized once into one image per page, and every download is reassembled from those, so no text from the original is ever selectable.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE CourseDocumentPage (
              id INT AUTO_INCREMENT NOT NULL,
              document_id INT NOT NULL,
              pageNumber INT NOT NULL,
              path VARCHAR(255) NOT NULL,
              width INT NOT NULL,
              height INT NOT NULL,
              INDEX IDX_455D13E2C33F7837 (document_id),
              UNIQUE INDEX UNIQ_455D13E2C33F78377D850928 (document_id, pageNumber),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql('ALTER TABLE CourseDocumentPage ADD CONSTRAINT FK_455D13E2C33F7837 FOREIGN KEY (document_id) REFERENCES CourseDocument (id)');

        $this->addSql('ALTER TABLE CourseDocument CHANGE filename path VARCHAR(255) NOT NULL, ADD flattenStatus VARCHAR(255) NOT NULL, ADD flattenedAt DATETIME DEFAULT NULL, ADD flattenError LONGTEXT DEFAULT NULL');

        // Existing documents keep their stored file under its new name and start out unprocessed, so the backfill
        // command picks every one of them up. Until it has run they are listed but not downloadable, which is the same
        // state a freshly uploaded document is in.
        $this->addSql("UPDATE CourseDocument SET flattenStatus = 'pending'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE CourseDocumentPage DROP FOREIGN KEY FK_455D13E2C33F7837');
        $this->addSql('DROP TABLE CourseDocumentPage');
        $this->addSql('ALTER TABLE CourseDocument CHANGE path filename VARCHAR(255) NOT NULL, DROP flattenStatus, DROP flattenedAt, DROP flattenError');
    }
}
