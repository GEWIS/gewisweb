<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * phpcs:disable Generic.Files.LineLength.TooLong
 * phpcs:disable SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall
 */
final class Version20260731182323 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Introduce agenda points, versioned meeting documents and minutes, the reference document library with per-meeting selection, local meeting details, and the meeting activity log; the previous document and minutes tables are kept as LegacyMeetingDocument and LegacyMeetingMinutes for the one-shot data migrator.';
    }

    public function up(Schema $schema): void
    {
        // Preserve the flat documents and minutes for the data migrator by renaming their tables out of the way. Their
        // foreign keys go first: the constraint names are derived from the original table names, and the new tables
        // need them. The renamed tables keep pointing at their meeting, under names derived from their new table names.
        $this->addSql('ALTER TABLE MeetingDocument DROP FOREIGN KEY FK_45407F4E602FAFFB96F82E16');
        $this->addSql('ALTER TABLE MeetingMinutes DROP FOREIGN KEY FK_5BE9DD26602FAFFB96F82E16');
        $this->addSql('RENAME TABLE MeetingDocument TO LegacyMeetingDocument, MeetingMinutes TO LegacyMeetingMinutes');
        $this->addSql('ALTER TABLE LegacyMeetingDocument RENAME INDEX IDX_45407F4E602FAFFB96F82E16 TO IDX_1FBA0797602FAFFB96F82E16');
        $this->addSql('ALTER TABLE LegacyMeetingDocument ADD CONSTRAINT FK_1FBA0797602FAFFB96F82E16 FOREIGN KEY (meeting_type, meeting_number) REFERENCES Meeting (type, number)');
        $this->addSql('ALTER TABLE LegacyMeetingMinutes ADD CONSTRAINT FK_A44E1FD8602FAFFB96F82E16 FOREIGN KEY (meeting_type, meeting_number) REFERENCES Meeting (type, number)');

        $this->addSql('CREATE TABLE MeetingPoint (number VARCHAR(16) NOT NULL, title VARCHAR(255) NOT NULL, displayPosition INT DEFAULT 0 NOT NULL, id INT AUTO_INCREMENT NOT NULL, createdAt DATETIME NOT NULL, updatedAt DATETIME NOT NULL, meeting_type ENUM(\'BV\', \'ALV\', \'VV\', \'Virt\') NOT NULL, meeting_number INT NOT NULL, INDEX IDX_DF818F11602FAFFB96F82E16 (meeting_type, meeting_number), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE MeetingDocument (name VARCHAR(255) NOT NULL, displayPosition INT DEFAULT 0 NOT NULL, id INT AUTO_INCREMENT NOT NULL, createdAt DATETIME NOT NULL, updatedAt DATETIME NOT NULL, meeting_type ENUM(\'BV\', \'ALV\', \'VV\', \'Virt\') NOT NULL, meeting_number INT NOT NULL, point_id INT DEFAULT NULL, INDEX IDX_45407F4E602FAFFB96F82E16 (meeting_type, meeting_number), INDEX IDX_45407F4EC028CEA2 (point_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE MeetingDocumentVersion (versionLabel VARCHAR(32) NOT NULL, path VARCHAR(255) NOT NULL, uploadedAt DATETIME DEFAULT NULL, id INT AUTO_INCREMENT NOT NULL, createdAt DATETIME NOT NULL, updatedAt DATETIME NOT NULL, uploadedBy INT DEFAULT NULL, document_id INT NOT NULL, INDEX IDX_6AD8AB29FE59E127 (uploadedBy), INDEX IDX_6AD8AB29C33F7837 (document_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE MeetingMinutes (meeting_type ENUM(\'BV\', \'ALV\', \'VV\', \'Virt\') NOT NULL, meeting_number INT NOT NULL, createdAt DATETIME NOT NULL, updatedAt DATETIME NOT NULL, PRIMARY KEY (meeting_type, meeting_number)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE MeetingMinutesVersion (versionLabel VARCHAR(32) NOT NULL, path VARCHAR(255) NOT NULL, uploadedAt DATETIME DEFAULT NULL, id INT AUTO_INCREMENT NOT NULL, createdAt DATETIME NOT NULL, updatedAt DATETIME NOT NULL, uploadedBy INT DEFAULT NULL, meeting_type ENUM(\'BV\', \'ALV\', \'VV\', \'Virt\') NOT NULL, meeting_number INT NOT NULL, INDEX IDX_4BA4C405FE59E127 (uploadedBy), INDEX IDX_4BA4C405602FAFFB96F82E16 (meeting_type, meeting_number), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE ReferenceDocument (name VARCHAR(255) NOT NULL, id INT AUTO_INCREMENT NOT NULL, createdAt DATETIME NOT NULL, updatedAt DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE ReferenceDocumentVersion (versionLabel VARCHAR(32) NOT NULL, path VARCHAR(255) NOT NULL, uploadedAt DATETIME DEFAULT NULL, id INT AUTO_INCREMENT NOT NULL, createdAt DATETIME NOT NULL, updatedAt DATETIME NOT NULL, uploadedBy INT DEFAULT NULL, referenceDocument_id INT NOT NULL, INDEX IDX_C40AE4F3FE59E127 (uploadedBy), INDEX IDX_C40AE4F372F632D7 (referenceDocument_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE MeetingReferenceSelection (id INT AUTO_INCREMENT NOT NULL, createdAt DATETIME NOT NULL, updatedAt DATETIME NOT NULL, meeting_type ENUM(\'BV\', \'ALV\', \'VV\', \'Virt\') NOT NULL, meeting_number INT NOT NULL, referenceDocument_id INT NOT NULL, pinnedVersion_id INT NOT NULL, INDEX IDX_2DFDBAF3602FAFFB96F82E16 (meeting_type, meeting_number), INDEX IDX_2DFDBAF372F632D7 (referenceDocument_id), INDEX IDX_2DFDBAF33551486 (pinnedVersion_id), UNIQUE INDEX meeting_reference_unique (meeting_type, meeting_number, referenceDocument_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE MeetingLocalDetails (meeting_type ENUM(\'BV\', \'ALV\', \'VV\', \'Virt\') NOT NULL, meeting_number INT NOT NULL, startTime TIME DEFAULT NULL, location VARCHAR(255) DEFAULT NULL, createdAt DATETIME NOT NULL, updatedAt DATETIME NOT NULL, PRIMARY KEY (meeting_type, meeting_number)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE MeetingActivityLog (verb ENUM(\'point_created\', \'point_updated\', \'point_deleted\', \'points_reordered\', \'document_uploaded\', \'document_version_uploaded\', \'document_renamed\', \'document_deleted\', \'documents_reordered\', \'minutes_uploaded\', \'minutes_deleted\', \'reference_selected\', \'reference_deselected\', \'reference_pinned\', \'reference_carried_over\', \'reference_document_created\', \'reference_document_renamed\', \'reference_document_deleted\', \'details_updated\') NOT NULL, subject VARCHAR(255) NOT NULL, createdAt DATETIME NOT NULL, id INT AUTO_INCREMENT NOT NULL, actor INT DEFAULT NULL, meeting_type ENUM(\'BV\', \'ALV\', \'VV\', \'Virt\') DEFAULT NULL, meeting_number INT DEFAULT NULL, INDEX IDX_51F2B2A2447556F9 (actor), INDEX IDX_51F2B2A2602FAFFB96F82E16 (meeting_type, meeting_number), INDEX meeting_activity_log_created_idx (createdAt), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');

        $this->addSql('ALTER TABLE MeetingPoint ADD CONSTRAINT FK_DF818F11602FAFFB96F82E16 FOREIGN KEY (meeting_type, meeting_number) REFERENCES Meeting (type, number)');
        $this->addSql('ALTER TABLE MeetingDocument ADD CONSTRAINT FK_45407F4E602FAFFB96F82E16 FOREIGN KEY (meeting_type, meeting_number) REFERENCES Meeting (type, number)');
        $this->addSql('ALTER TABLE MeetingDocument ADD CONSTRAINT FK_45407F4EC028CEA2 FOREIGN KEY (point_id) REFERENCES MeetingPoint (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE MeetingDocumentVersion ADD CONSTRAINT FK_6AD8AB29FE59E127 FOREIGN KEY (uploadedBy) REFERENCES User (lidnr) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE MeetingDocumentVersion ADD CONSTRAINT FK_6AD8AB29C33F7837 FOREIGN KEY (document_id) REFERENCES MeetingDocument (id)');
        $this->addSql('ALTER TABLE MeetingMinutes ADD CONSTRAINT FK_5BE9DD26602FAFFB96F82E16 FOREIGN KEY (meeting_type, meeting_number) REFERENCES Meeting (type, number)');
        $this->addSql('ALTER TABLE MeetingMinutesVersion ADD CONSTRAINT FK_4BA4C405FE59E127 FOREIGN KEY (uploadedBy) REFERENCES User (lidnr) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE MeetingMinutesVersion ADD CONSTRAINT FK_4BA4C405602FAFFB96F82E16 FOREIGN KEY (meeting_type, meeting_number) REFERENCES MeetingMinutes (meeting_type, meeting_number)');
        $this->addSql('ALTER TABLE ReferenceDocumentVersion ADD CONSTRAINT FK_C40AE4F3FE59E127 FOREIGN KEY (uploadedBy) REFERENCES User (lidnr) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE ReferenceDocumentVersion ADD CONSTRAINT FK_C40AE4F372F632D7 FOREIGN KEY (referenceDocument_id) REFERENCES ReferenceDocument (id)');
        $this->addSql('ALTER TABLE MeetingReferenceSelection ADD CONSTRAINT FK_2DFDBAF3602FAFFB96F82E16 FOREIGN KEY (meeting_type, meeting_number) REFERENCES Meeting (type, number)');
        $this->addSql('ALTER TABLE MeetingReferenceSelection ADD CONSTRAINT FK_2DFDBAF372F632D7 FOREIGN KEY (referenceDocument_id) REFERENCES ReferenceDocument (id)');
        $this->addSql('ALTER TABLE MeetingReferenceSelection ADD CONSTRAINT FK_2DFDBAF33551486 FOREIGN KEY (pinnedVersion_id) REFERENCES ReferenceDocumentVersion (id)');
        $this->addSql('ALTER TABLE MeetingLocalDetails ADD CONSTRAINT FK_FB2E677602FAFFB96F82E16 FOREIGN KEY (meeting_type, meeting_number) REFERENCES Meeting (type, number)');
        $this->addSql('ALTER TABLE MeetingActivityLog ADD CONSTRAINT FK_51F2B2A2447556F9 FOREIGN KEY (actor) REFERENCES User (lidnr) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE MeetingActivityLog ADD CONSTRAINT FK_51F2B2A2602FAFFB96F82E16 FOREIGN KEY (meeting_type, meeting_number) REFERENCES Meeting (type, number)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE MeetingActivityLog DROP FOREIGN KEY FK_51F2B2A2447556F9');
        $this->addSql('ALTER TABLE MeetingActivityLog DROP FOREIGN KEY FK_51F2B2A2602FAFFB96F82E16');
        $this->addSql('ALTER TABLE MeetingLocalDetails DROP FOREIGN KEY FK_FB2E677602FAFFB96F82E16');
        $this->addSql('ALTER TABLE MeetingReferenceSelection DROP FOREIGN KEY FK_2DFDBAF3602FAFFB96F82E16');
        $this->addSql('ALTER TABLE MeetingReferenceSelection DROP FOREIGN KEY FK_2DFDBAF372F632D7');
        $this->addSql('ALTER TABLE MeetingReferenceSelection DROP FOREIGN KEY FK_2DFDBAF33551486');
        $this->addSql('ALTER TABLE ReferenceDocumentVersion DROP FOREIGN KEY FK_C40AE4F3FE59E127');
        $this->addSql('ALTER TABLE ReferenceDocumentVersion DROP FOREIGN KEY FK_C40AE4F372F632D7');
        $this->addSql('ALTER TABLE MeetingMinutesVersion DROP FOREIGN KEY FK_4BA4C405FE59E127');
        $this->addSql('ALTER TABLE MeetingMinutesVersion DROP FOREIGN KEY FK_4BA4C405602FAFFB96F82E16');
        $this->addSql('ALTER TABLE MeetingMinutes DROP FOREIGN KEY FK_5BE9DD26602FAFFB96F82E16');
        $this->addSql('ALTER TABLE MeetingDocumentVersion DROP FOREIGN KEY FK_6AD8AB29FE59E127');
        $this->addSql('ALTER TABLE MeetingDocumentVersion DROP FOREIGN KEY FK_6AD8AB29C33F7837');
        $this->addSql('ALTER TABLE MeetingDocument DROP FOREIGN KEY FK_45407F4E602FAFFB96F82E16');
        $this->addSql('ALTER TABLE MeetingDocument DROP FOREIGN KEY FK_45407F4EC028CEA2');
        $this->addSql('ALTER TABLE MeetingPoint DROP FOREIGN KEY FK_DF818F11602FAFFB96F82E16');
        $this->addSql('ALTER TABLE LegacyMeetingDocument DROP FOREIGN KEY FK_1FBA0797602FAFFB96F82E16');
        $this->addSql('ALTER TABLE LegacyMeetingMinutes DROP FOREIGN KEY FK_A44E1FD8602FAFFB96F82E16');
        $this->addSql('DROP TABLE MeetingActivityLog');
        $this->addSql('DROP TABLE MeetingLocalDetails');
        $this->addSql('DROP TABLE MeetingReferenceSelection');
        $this->addSql('DROP TABLE ReferenceDocumentVersion');
        $this->addSql('DROP TABLE ReferenceDocument');
        $this->addSql('DROP TABLE MeetingMinutesVersion');
        $this->addSql('DROP TABLE MeetingMinutes');
        $this->addSql('DROP TABLE MeetingDocumentVersion');
        $this->addSql('DROP TABLE MeetingDocument');
        $this->addSql('DROP TABLE MeetingPoint');
        $this->addSql('RENAME TABLE LegacyMeetingDocument TO MeetingDocument, LegacyMeetingMinutes TO MeetingMinutes');
        $this->addSql('ALTER TABLE MeetingDocument RENAME INDEX IDX_1FBA0797602FAFFB96F82E16 TO IDX_45407F4E602FAFFB96F82E16');
        $this->addSql('ALTER TABLE MeetingDocument ADD CONSTRAINT FK_45407F4E602FAFFB96F82E16 FOREIGN KEY (meeting_type, meeting_number) REFERENCES Meeting (type, number)');
        $this->addSql('ALTER TABLE MeetingMinutes ADD CONSTRAINT FK_5BE9DD26602FAFFB96F82E16 FOREIGN KEY (meeting_type, meeting_number) REFERENCES Meeting (type, number)');
    }
}
