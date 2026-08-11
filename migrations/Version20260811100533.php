<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * phpcs:disable Generic.Files.LineLength.TooLong
 * phpcs:disable SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall
 */
final class Version20260811100533 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Put what a body writes about itself through the same review workflow as an activity or a company profile. Until now a body owned two rows at once, one approved and one not, and approving the second deleted the first: no history, no reason given, no way back. A page is now one thing with a chain of revisions behind it, so the board can see what changed and say why, and nothing that was ever published is thrown away. What is approved today becomes the first revision and stays live; anything that was waiting becomes a second revision that is still waiting.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE DecisionLocalisedText (
              id INT AUTO_INCREMENT NOT NULL,
              valueEN LONGTEXT DEFAULT NULL,
              valueNL LONGTEXT DEFAULT NULL,
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
            SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE OrganInformationRevision (
              id INT AUTO_INCREMENT NOT NULL,
              organInformation_id INT NOT NULL,
              previousRevision_id INT DEFAULT NULL,
              shortDescription_id INT NOT NULL,
              description_id INT NOT NULL,
              author_id INT DEFAULT NULL,
              authorCompanyUser_id INT DEFAULT NULL,
              reviewer_id INT DEFAULT NULL,
              lastEditedBy_id INT DEFAULT NULL,
              lastEditedByCompanyUser_id INT DEFAULT NULL,
              status VARCHAR(255) NOT NULL,
              revisionNumber INT NOT NULL,
              reviewedAt DATETIME DEFAULT NULL,
              version INT DEFAULT 1 NOT NULL,
              email VARCHAR(255) DEFAULT NULL,
              website VARCHAR(255) DEFAULT NULL,
              bannerSource VARCHAR(255) DEFAULT NULL,
              bannerCrop JSON DEFAULT NULL,
              bannerPath VARCHAR(255) DEFAULT NULL,
              logoSource VARCHAR(255) DEFAULT NULL,
              logoCrop JSON DEFAULT NULL,
              logoPath VARCHAR(255) DEFAULT NULL,
              createdAt DATETIME NOT NULL,
              updatedAt DATETIME NOT NULL,
              INDEX IDX_61242DD5679B608D (organInformation_id),
              INDEX IDX_61242DD58F2D4199 (previousRevision_id),
              UNIQUE INDEX UNIQ_61242DD581CB52B6 (shortDescription_id),
              UNIQUE INDEX UNIQ_61242DD5D9F966B (description_id),
              INDEX IDX_61242DD5F675F31B (author_id),
              INDEX IDX_61242DD5FD16CEE4 (authorCompanyUser_id),
              INDEX IDX_61242DD570574616 (reviewer_id),
              INDEX IDX_61242DD5A19E445F (lastEditedBy_id),
              INDEX IDX_61242DD5102DD120 (lastEditedByCompanyUser_id),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
            SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE OrganSocialLink (
              id INT AUTO_INCREMENT NOT NULL,
              revision_id INT NOT NULL,
              platform VARCHAR(255) NOT NULL,
              handle VARCHAR(255) NOT NULL,
              INDEX IDX_3977FF801DFA7C8F (revision_id),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
            SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE OrganInformationRevisionComment (
              id INT AUTO_INCREMENT NOT NULL,
              revision_id INT NOT NULL,
              author_id INT DEFAULT NULL,
              authorCompanyUser_id INT DEFAULT NULL,
              body LONGTEXT NOT NULL,
              createdAt DATETIME NOT NULL,
              updatedAt DATETIME NOT NULL,
              INDEX IDX_AEDB48581DFA7C8F (revision_id),
              INDEX IDX_AEDB4858F675F31B (author_id),
              INDEX IDX_AEDB4858FD16CEE4 (authorCompanyUser_id),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
            SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE OrganInformationRevision
              ADD CONSTRAINT FK_61242DD5679B608D FOREIGN KEY (organInformation_id) REFERENCES OrganInformation (id),
              ADD CONSTRAINT FK_61242DD58F2D4199 FOREIGN KEY (previousRevision_id) REFERENCES OrganInformationRevision (id),
              ADD CONSTRAINT FK_61242DD581CB52B6 FOREIGN KEY (shortDescription_id) REFERENCES DecisionLocalisedText (id),
              ADD CONSTRAINT FK_61242DD5D9F966B FOREIGN KEY (description_id) REFERENCES DecisionLocalisedText (id),
              ADD CONSTRAINT FK_61242DD5F675F31B FOREIGN KEY (author_id) REFERENCES Member (lidnr),
              ADD CONSTRAINT FK_61242DD5FD16CEE4 FOREIGN KEY (authorCompanyUser_id) REFERENCES CompanyUser (id) ON DELETE SET NULL,
              ADD CONSTRAINT FK_61242DD570574616 FOREIGN KEY (reviewer_id) REFERENCES Member (lidnr),
              ADD CONSTRAINT FK_61242DD5A19E445F FOREIGN KEY (lastEditedBy_id) REFERENCES User (lidnr),
              ADD CONSTRAINT FK_61242DD5102DD120 FOREIGN KEY (lastEditedByCompanyUser_id) REFERENCES CompanyUser (id) ON DELETE SET NULL
            SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE OrganSocialLink
              ADD CONSTRAINT FK_3977FF801DFA7C8F FOREIGN KEY (revision_id) REFERENCES OrganInformationRevision (id)
            SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE OrganInformationRevisionComment
              ADD CONSTRAINT FK_AEDB48581DFA7C8F FOREIGN KEY (revision_id) REFERENCES OrganInformationRevision (id),
              ADD CONSTRAINT FK_AEDB4858F675F31B FOREIGN KEY (author_id) REFERENCES User (lidnr),
              ADD CONSTRAINT FK_AEDB4858FD16CEE4 FOREIGN KEY (authorCompanyUser_id) REFERENCES CompanyUser (id) ON DELETE SET NULL
            SQL);

        // The pointers and the timestamps the aggregate gains. A default carries the existing rows over the NOT NULL and
        // is dropped again below, so the column ends up exactly as the mapping describes it.
        $this->addSql(<<<'SQL'
            ALTER TABLE OrganInformation
              ADD currentRevision_id INT DEFAULT NULL,
              ADD liveRevision_id INT DEFAULT NULL,
              ADD createdAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              ADD updatedAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            SQL);

        // Which of a body's rows survives as the page itself: the approved one when there is one, and otherwise the only
        // row there is. Whatever else it had becomes a revision hanging off that survivor.
        $this->addSql(<<<'SQL'
            ALTER TABLE OrganInformation ADD tmp_keep TINYINT(1) DEFAULT 0 NOT NULL
            SQL);
        $this->addSql(<<<'SQL'
            UPDATE OrganInformation information
            JOIN (
              SELECT organ_id,
                     COALESCE(MIN(CASE WHEN approver_id IS NOT NULL THEN id END), MIN(id)) AS keep_id
              FROM OrganInformation
              GROUP BY organ_id
            ) survivor ON survivor.keep_id = information.id
            SET information.tmp_keep = 1
            SQL);

        // The descriptions become localised texts of their own, carrying the row they came from so the revisions below
        // can find them again.
        $this->addSql(<<<'SQL'
            ALTER TABLE DecisionLocalisedText
              ADD tmp_source INT DEFAULT NULL,
              ADD tmp_kind VARCHAR(16) DEFAULT NULL
            SQL);
        $this->addSql(<<<'SQL'
            INSERT INTO DecisionLocalisedText (valueEN, valueNL, tmp_source, tmp_kind)
            SELECT shortEnglishDescription, shortDutchDescription, id, 'short' FROM OrganInformation
            SQL);
        $this->addSql(<<<'SQL'
            INSERT INTO DecisionLocalisedText (valueEN, valueNL, tmp_source, tmp_kind)
            SELECT englishDescription, dutchDescription, id, 'long' FROM OrganInformation
            SQL);

        // Every row becomes a revision of the page that survived for its body, numbered in order with what was approved
        // first, so a body that kept more than one row of either kind still gets a chain rather than two revisions
        // sharing a number. Nothing recorded who wrote a page or when it was approved, so neither is invented here.
        $this->addSql(<<<'SQL'
            INSERT INTO OrganInformationRevision (
              organInformation_id, shortDescription_id, description_id, reviewer_id,
              status, revisionNumber, version,
              email, website, bannerSource, bannerCrop, bannerPath, logoSource, logoCrop, logoPath,
              createdAt, updatedAt
            )
            SELECT
              survivor.id,
              shortText.id,
              descriptionText.id,
              information.approver_id,
              CASE WHEN information.approver_id IS NULL THEN 'submitted' ELSE 'approved' END,
              ROW_NUMBER() OVER (
                PARTITION BY information.organ_id
                ORDER BY (information.approver_id IS NULL), information.id
              ),
              1,
              information.email,
              information.website,
              information.coverPath,
              CASE WHEN information.coverPath IS NULL THEN NULL ELSE '{"x": 0, "y": 0, "width": 1, "height": 1}' END,
              information.coverPath,
              information.thumbnailPath,
              CASE WHEN information.thumbnailPath IS NULL THEN NULL ELSE '{"x": 0, "y": 0, "width": 1, "height": 1}' END,
              information.thumbnailPath,
              NOW(),
              NOW()
            FROM OrganInformation information
            JOIN OrganInformation survivor
              ON survivor.organ_id = information.organ_id AND survivor.tmp_keep = 1
            JOIN DecisionLocalisedText shortText
              ON shortText.tmp_source = information.id AND shortText.tmp_kind = 'short'
            JOIN DecisionLocalisedText descriptionText
              ON descriptionText.tmp_source = information.id AND descriptionText.tmp_kind = 'long'
            SQL);

        $this->addSql(<<<'SQL'
            UPDATE OrganInformationRevision later
            JOIN OrganInformationRevision earlier
              ON earlier.organInformation_id = later.organInformation_id
             AND earlier.revisionNumber = later.revisionNumber - 1
            SET later.previousRevision_id = earlier.id
            SQL);

        // What visitors see is the last thing the board agreed to, which is the highest-numbered approved revision.
        $this->addSql(<<<'SQL'
            UPDATE OrganInformation information
            JOIN (
              SELECT organInformation_id, MAX(revisionNumber) AS highest
              FROM OrganInformationRevision
              WHERE status = 'approved'
              GROUP BY organInformation_id
            ) live ON live.organInformation_id = information.id
            JOIN OrganInformationRevision revision
              ON revision.organInformation_id = information.id AND revision.revisionNumber = live.highest
            SET information.liveRevision_id = revision.id
            SQL);

        $this->addSql(<<<'SQL'
            UPDATE OrganInformation information
            JOIN (
              SELECT organInformation_id, MAX(revisionNumber) AS highest
              FROM OrganInformationRevision
              GROUP BY organInformation_id
            ) head ON head.organInformation_id = information.id
            JOIN OrganInformationRevision revision
              ON revision.organInformation_id = information.id AND revision.revisionNumber = head.highest
            SET information.currentRevision_id = revision.id
            SQL);

        $this->addSql(<<<'SQL'
            DELETE FROM OrganInformation WHERE tmp_keep = 0
            SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE DecisionLocalisedText DROP tmp_source, DROP tmp_kind
            SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE OrganInformation DROP FOREIGN KEY `FK_DD810E34BB23766C`
            SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_DD810E34BB23766C ON OrganInformation
            SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE OrganInformation
              DROP tmp_keep,
              DROP approver_id,
              DROP email,
              DROP website,
              DROP shortDutchDescription,
              DROP dutchDescription,
              DROP shortEnglishDescription,
              DROP englishDescription,
              DROP coverPath,
              DROP thumbnailPath,
              MODIFY createdAt DATETIME NOT NULL,
              MODIFY updatedAt DATETIME NOT NULL
            SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE OrganInformation
              DROP INDEX IDX_DD810E34E4445171,
              ADD UNIQUE INDEX organ_information_organ_uniq (organ_id)
            SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE OrganInformation
              ADD CONSTRAINT FK_DD810E342796CA52 FOREIGN KEY (currentRevision_id) REFERENCES OrganInformationRevision (id),
              ADD CONSTRAINT FK_DD810E34A892657C FOREIGN KEY (liveRevision_id) REFERENCES OrganInformationRevision (id)
            SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_DD810E342796CA52 ON OrganInformation (currentRevision_id)
            SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_DD810E34A892657C ON OrganInformation (liveRevision_id)
            SQL);
    }

    public function down(Schema $schema): void
    {
        // The content columns come back and are filled from whichever revision was live, or from the working head when
        // nothing was. The rest of the chain, the discussion and the social links do not survive going back.
        $this->addSql(<<<'SQL'
            ALTER TABLE OrganInformation
              ADD approver_id INT DEFAULT NULL,
              ADD email VARCHAR(255) DEFAULT NULL,
              ADD website VARCHAR(255) DEFAULT NULL,
              ADD shortDutchDescription VARCHAR(255) DEFAULT NULL,
              ADD dutchDescription LONGTEXT DEFAULT NULL,
              ADD shortEnglishDescription VARCHAR(255) DEFAULT NULL,
              ADD englishDescription LONGTEXT DEFAULT NULL,
              ADD coverPath VARCHAR(255) DEFAULT NULL,
              ADD thumbnailPath VARCHAR(255) DEFAULT NULL
            SQL);

        $this->addSql(<<<'SQL'
            UPDATE OrganInformation information
            JOIN OrganInformationRevision revision
              ON revision.id = COALESCE(information.liveRevision_id, information.currentRevision_id)
            JOIN DecisionLocalisedText shortText ON shortText.id = revision.shortDescription_id
            JOIN DecisionLocalisedText descriptionText ON descriptionText.id = revision.description_id
            SET information.approver_id = revision.reviewer_id,
                information.email = revision.email,
                information.website = revision.website,
                information.shortEnglishDescription = LEFT(COALESCE(shortText.valueEN, ''), 255),
                information.shortDutchDescription = LEFT(COALESCE(shortText.valueNL, ''), 255),
                information.englishDescription = descriptionText.valueEN,
                information.dutchDescription = descriptionText.valueNL,
                information.coverPath = revision.bannerPath,
                information.thumbnailPath = revision.logoPath
            SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE OrganInformation
              DROP FOREIGN KEY `FK_DD810E342796CA52`,
              DROP FOREIGN KEY `FK_DD810E34A892657C`
            SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE OrganInformation
              DROP INDEX organ_information_organ_uniq,
              ADD INDEX IDX_DD810E34E4445171 (organ_id)
            SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE OrganInformation
              DROP currentRevision_id,
              DROP liveRevision_id,
              DROP createdAt,
              DROP updatedAt
            SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE OrganInformation
              ADD CONSTRAINT FK_DD810E34BB23766C FOREIGN KEY (approver_id) REFERENCES Member (lidnr)
            SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_DD810E34BB23766C ON OrganInformation (approver_id)
            SQL);

        $this->addSql(<<<'SQL'
            DROP TABLE OrganInformationRevisionComment
            SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE OrganSocialLink
            SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE OrganInformationRevision
            SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE DecisionLocalisedText
            SQL);
    }
}
