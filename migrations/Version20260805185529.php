<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * phpcs:disable Generic.Files.LineLength.TooLong
 * phpcs:disable SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall
 */
final class Version20260805185529 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Hold uploaded course documents until somebody has said what they are. A batch of exams from the department arrives named to no standard, so an upload lands here first with everything guessed from its filename, and is only filed under a course once an administrator has confirmed the guesses.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE CourseDocumentStaging (
              id INT AUTO_INCREMENT NOT NULL,
              uploaded_by INT DEFAULT NULL,
              originalFilename VARCHAR(255) NOT NULL,
              path VARCHAR(255) NOT NULL,
              uploadedAt DATETIME NOT NULL,
              courseCode VARCHAR(255) DEFAULT NULL,
              date DATE DEFAULT NULL,
              language VARCHAR(255) NOT NULL,
              type VARCHAR(255) NOT NULL,
              examType VARCHAR(255) DEFAULT NULL,
              author VARCHAR(255) DEFAULT NULL,
              scanned TINYINT NOT NULL,
              INDEX IDX_90647A1E3E73126 (uploaded_by),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);

        // Who uploaded it is only there so a batch can be traced back; the row outlives the account.
        $this->addSql('ALTER TABLE CourseDocumentStaging ADD CONSTRAINT FK_90647A1E3E73126 FOREIGN KEY (uploaded_by) REFERENCES User (lidnr) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE CourseDocumentStaging DROP FOREIGN KEY FK_90647A1E3E73126');
        $this->addSql('DROP TABLE CourseDocumentStaging');
    }
}
