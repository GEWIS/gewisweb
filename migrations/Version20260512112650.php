<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * phpcs:disable Generic.Files.LineLength.TooLong
 * phpcs:disable SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall
 */
final class Version20260512112650 extends AbstractMigration
{
    /** @var string[] */
    private array $tables = [
        'Activity',
        'ActivityCalendarOption',
        'ActivityCategory',
        'ActivityCategoryAssignment',
        'ActivityLocalisedText',
        'ActivityOptionCreationPeriod',
        'ActivityOptionProposal',
        'ActivityUpdateProposal',
        'Address',
        'Album',
        'ApiApp',
        'ApiAppAuthentication',
        'ApiUser',
        'ApprovableText',
        'Authorization',
        'Company',
        'CompanyLocalisedText',
        'CompanyPackage',
        'CompanyUpdate',
        'CompanyUser',
        'FrontpageLocalisedText',
        'Job',
        'JobCategory',
        'JobLabel',
        'JobLabelAssignment',
        'JobUpdate',
        'MaxActivities',
        'Member',
        'NewCompanyUser',
        'NewUser',
        'NewsItem',
        'OrganInformation',
        'Page',
        'Photo',
        'Poll',
        'PollComment',
        'PollOption',
        'PollVote',
        'ProfilePhoto',
        'Signup',
        'SignupField',
        'SignupFieldValue',
        'SignupList',
        'SignupOption',
        'Tag',
        'User',
        'UserRole',
        'Vote',
        'WeeklyPhoto',
    ];

    /** @var string[] */
    private array $specialTables = [
        'BoardMember',
        'Course',
        'CourseDocument',
        'Decision',
        'Keyholder',
        'MailingList',
        'MailingListMember',
        'Meeting',
        'MeetingDocument',
        'MeetingMinutes',
        'Organ',
        'OrganMember',
        'organs_subdecisions',
        'SimilarCourse',
        'SubDecision',
    ];

    public function getDescription(): string
    {
        return 'Initial migration after switching from Laminas to Symfony (moves from utf8mb4_unicode_ci to utf8mb4_uca1400_ai_ci).';
    }

    public function up(Schema $schema): void
    {
        // Clean-up what is no longer necessary:
        $this->addSql('ALTER TABLE LoginAttempt DROP FOREIGN KEY `FK_C137201B979B1AD6`');
        $this->addSql('ALTER TABLE LoginAttempt DROP FOREIGN KEY `FK_C137201BA76ED395`');
        $this->addSql('DROP TABLE LoginAttempt');

        // Change character set and collation for the database:
        $this->addSql('ALTER DATABASE CHARACTER SET utf8mb4 COLLATE utf8mb4_uca1400_ai_ci');

        // Do all normal tables first.
        foreach ($this->tables as $table) {
            $this->addSql('ALTER TABLE ' . $table . ' CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_uca1400_ai_ci');
        }

        // Now the fun part, doing all tables with FKs on columns that (may) need to change sizes. First removal:
        $this->addSql('ALTER TABLE BoardMember DROP FOREIGN KEY FK_D9517B2EEFBA85FF292FAD512F37B76A76CE1878B79BB36');
        $this->addSql('ALTER TABLE SimilarCourse DROP FOREIGN KEY FK_C56C679ABFB7ED9E');
        $this->addSql('ALTER TABLE SimilarCourse DROP FOREIGN KEY FK_C56C679ACD579B90');
        $this->addSql('ALTER TABLE CourseDocument DROP FOREIGN KEY FK_90F07469BFB7ED9E');
        $this->addSql('ALTER TABLE Decision DROP FOREIGN KEY FK_7DDADC1E602FAFFB96F82E16');
        $this->addSql('ALTER TABLE Keyholder DROP FOREIGN KEY FK_3C5F7B4DEFBA85FF292FAD512F37B76A76CE1878B79BB36');
        $this->addSql('ALTER TABLE MailingListMember DROP FOREIGN KEY FK_3A8467A97B1AC3ED');
        $this->addSql('ALTER TABLE MeetingDocument DROP FOREIGN KEY FK_45407F4E602FAFFB96F82E16');
        $this->addSql('ALTER TABLE MeetingMinutes DROP FOREIGN KEY FK_5BE9DD26602FAFFB96F82E16');
        $this->addSql('ALTER TABLE Organ DROP FOREIGN KEY FK_46C39B8EEFBA85FF292FAD512F37B76A76CE1878B79BB36');
        $this->addSql('ALTER TABLE organs_subdecisions DROP FOREIGN KEY FK_6177E308602FAFFB96F82E1690E0342DEF6BE237DD50EB88');
        $this->addSql('ALTER TABLE OrganMember DROP FOREIGN KEY FK_E5CB2C7DEFBA85FF292FAD512F37B76A76CE1878B79BB36');
        $this->addSql('ALTER TABLE SubDecision DROP FOREIGN KEY FK_F0D6EE40602FAFFB96F82E1690E0342DEF6BE237');
        $this->addSql('ALTER TABLE SubDecision DROP FOREIGN KEY FK_F0D6EE40EFBA85FF292FAD51');
        $this->addSql('ALTER TABLE SubDecision DROP FOREIGN KEY FK_F0D6EE40EFBA85FF292FAD512F37B76A76CE187');
        $this->addSql('ALTER TABLE SubDecision DROP FOREIGN KEY FK_F0D6EE40EFBA85FF292FAD512F37B76A76CE1878B79BB36');

        // Then, fix the charset and collation:
        foreach ($this->specialTables as $table) {
            $this->addSql('ALTER TABLE ' . $table . ' CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_uca1400_ai_ci');
        }

        // Change the type for meetings (do this before restoring the FKs):
        $this->addSql('ALTER TABLE BoardMember CHANGE r_meeting_type r_meeting_type ENUM(\'BV\', \'ALV\', \'VV\', \'Virt\') DEFAULT NULL');
        $this->addSql('ALTER TABLE Decision CHANGE meeting_type meeting_type ENUM(\'BV\', \'ALV\', \'VV\', \'Virt\') NOT NULL');
        $this->addSql('ALTER TABLE Keyholder CHANGE r_meeting_type r_meeting_type ENUM(\'BV\', \'ALV\', \'VV\', \'Virt\') DEFAULT NULL');
        $this->addSql('ALTER TABLE Meeting CHANGE type type ENUM(\'BV\', \'ALV\', \'VV\', \'Virt\') NOT NULL');
        $this->addSql('ALTER TABLE MeetingDocument CHANGE meeting_type meeting_type ENUM(\'BV\', \'ALV\', \'VV\', \'Virt\') DEFAULT NULL');
        $this->addSql('ALTER TABLE MeetingMinutes CHANGE meeting_type meeting_type ENUM(\'BV\', \'ALV\', \'VV\', \'Virt\') NOT NULL');
        $this->addSql('ALTER TABLE Organ CHANGE r_meeting_type r_meeting_type ENUM(\'BV\', \'ALV\', \'VV\', \'Virt\') DEFAULT NULL');
        $this->addSql('ALTER TABLE organs_subdecisions CHANGE meeting_type meeting_type ENUM(\'BV\', \'ALV\', \'VV\', \'Virt\') NOT NULL');
        $this->addSql('ALTER TABLE OrganMember CHANGE r_meeting_type r_meeting_type ENUM(\'BV\', \'ALV\', \'VV\', \'Virt\') DEFAULT NULL');
        $this->addSql('ALTER TABLE SubDecision CHANGE meeting_type meeting_type ENUM(\'BV\', \'ALV\', \'VV\', \'Virt\') NOT NULL, CHANGE r_meeting_type r_meeting_type ENUM(\'BV\', \'ALV\', \'VV\', \'Virt\') DEFAULT NULL');

        // Finally, restore FKs (and fix some indexes):
        $this->addSql('ALTER TABLE BoardMember DROP INDEX installationDec_uniq, ADD INDEX IDX_D9517B2EEFBA85FF292FAD512F37B76A76CE1878B79BB36 (r_meeting_type, r_meeting_number, r_decision_point, r_decision_number, r_sequence)');
        $this->addSql('ALTER TABLE BoardMember ADD CONSTRAINT FK_D9517B2EEFBA85FF292FAD512F37B76A76CE1878B79BB36 FOREIGN KEY (r_meeting_type, r_meeting_number, r_decision_point, r_decision_number, r_sequence) REFERENCES SubDecision (meeting_type, meeting_number, decision_point, decision_number, sequence)');
        $this->addSql('ALTER TABLE SimilarCourse ADD CONSTRAINT FK_C56C679ABFB7ED9E FOREIGN KEY (course_code) REFERENCES Course (code)');
        $this->addSql('ALTER TABLE SimilarCourse ADD CONSTRAINT FK_C56C679ACD579B90 FOREIGN KEY (similar_course_code) REFERENCES Course (code)');
        $this->addSql('ALTER TABLE CourseDocument ADD CONSTRAINT FK_90F07469BFB7ED9E FOREIGN KEY (course_code) REFERENCES Course (code)');
        $this->addSql('ALTER TABLE Decision ADD CONSTRAINT FK_7DDADC1E602FAFFB96F82E16 FOREIGN KEY (meeting_type, meeting_number) REFERENCES Meeting (type, number)');
        $this->addSql('ALTER TABLE Keyholder DROP INDEX grantingDec_uniq, ADD INDEX IDX_3C5F7B4DEFBA85FF292FAD512F37B76A76CE1878B79BB36 (r_meeting_type, r_meeting_number, r_decision_point, r_decision_number, r_sequence)');
        $this->addSql('ALTER TABLE Keyholder ADD CONSTRAINT FK_3C5F7B4DEFBA85FF292FAD512F37B76A76CE1878B79BB36 FOREIGN KEY (r_meeting_type, r_meeting_number, r_decision_point, r_decision_number, r_sequence) REFERENCES SubDecision (meeting_type, meeting_number, decision_point, decision_number, sequence)');
        $this->addSql('ALTER TABLE MailingListMember ADD CONSTRAINT FK_3A8467A97B1AC3ED FOREIGN KEY (mailingList) REFERENCES MailingList (name)');
        $this->addSql('ALTER TABLE MeetingDocument ADD CONSTRAINT FK_45407F4E602FAFFB96F82E16 FOREIGN KEY (meeting_type, meeting_number) REFERENCES Meeting (type, number)');
        $this->addSql('DROP INDEX meeting_uniq ON MeetingMinutes');
        $this->addSql('ALTER TABLE MeetingMinutes ADD CONSTRAINT FK_5BE9DD26602FAFFB96F82E16 FOREIGN KEY (meeting_type, meeting_number) REFERENCES Meeting (type, number)');
        $this->addSql('ALTER TABLE Organ DROP INDEX foundation_uniq, ADD INDEX IDX_46C39B8EEFBA85FF292FAD512F37B76A76CE1878B79BB36 (r_meeting_type, r_meeting_number, r_decision_point, r_decision_number, r_sequence)');
        $this->addSql('ALTER TABLE Organ ADD CONSTRAINT FK_46C39B8EEFBA85FF292FAD512F37B76A76CE1878B79BB36 FOREIGN KEY (r_meeting_type, r_meeting_number, r_decision_point, r_decision_number, r_sequence) REFERENCES SubDecision (meeting_type, meeting_number, decision_point, decision_number, sequence)');
        $this->addSql('ALTER TABLE organs_subdecisions ADD CONSTRAINT FK_6177E308602FAFFB96F82E1690E0342DEF6BE237DD50EB88 FOREIGN KEY (meeting_type, meeting_number, decision_point, decision_number, subdecision_sequence) REFERENCES SubDecision (meeting_type, meeting_number, decision_point, decision_number, sequence)');
        $this->addSql('ALTER TABLE OrganMember DROP INDEX installation_uniq, ADD INDEX IDX_E5CB2C7DEFBA85FF292FAD512F37B76A76CE1878B79BB36 (r_meeting_type, r_meeting_number, r_decision_point, r_decision_number, r_sequence)');
        $this->addSql('ALTER TABLE OrganMember ADD CONSTRAINT FK_E5CB2C7DEFBA85FF292FAD512F37B76A76CE1878B79BB36 FOREIGN KEY (r_meeting_type, r_meeting_number, r_decision_point, r_decision_number, r_sequence) REFERENCES SubDecision (meeting_type, meeting_number, decision_point, decision_number, sequence)');
        $this->addSql('ALTER TABLE SubDecision ADD CONSTRAINT FK_F0D6EE40602FAFFB96F82E1690E0342DEF6BE237 FOREIGN KEY (meeting_type, meeting_number, decision_point, decision_number) REFERENCES Decision (meeting_type, meeting_number, point, number)');
        $this->addSql('ALTER TABLE SubDecision ADD CONSTRAINT FK_F0D6EE40EFBA85FF292FAD512F37B76A76CE1878B79BB36 FOREIGN KEY (r_meeting_type, r_meeting_number, r_decision_point, r_decision_number, r_sequence) REFERENCES SubDecision (meeting_type, meeting_number, decision_point, decision_number, sequence)');
        $this->addSql('ALTER TABLE SubDecision ADD CONSTRAINT FK_F0D6EE40EFBA85FF292FAD512F37B76A76CE187 FOREIGN KEY (r_meeting_type, r_meeting_number, r_decision_point, r_decision_number) REFERENCES Decision (meeting_type, meeting_number, point, number)');
        $this->addSql('ALTER TABLE SubDecision ADD CONSTRAINT FK_F0D6EE40EFBA85FF292FAD51 FOREIGN KEY (r_meeting_type, r_meeting_number) REFERENCES Meeting (type, number)');

        // Part of the migration to Symfony:
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_uca1400_ai_ci` ENGINE = InnoDB');

        // Change `Company` to `Career`:
        $this->addSql('ALTER TABLE Company DROP FOREIGN KEY FK_800230D326C79F4B');
        $this->addSql('ALTER TABLE Company DROP FOREIGN KEY FK_800230D3D9F966B');
        $this->addSql('ALTER TABLE Company DROP FOREIGN KEY FK_800230D318F45C82');
        $this->addSql('ALTER TABLE CompanyPackage DROP FOREIGN KEY FK_181DA5277294869C');
        $this->addSql('ALTER TABLE JobCategory DROP FOREIGN KEY FK_9701A2A6A8BEABC');
        $this->addSql('ALTER TABLE JobCategory DROP FOREIGN KEY FK_9701A2A6311966CE');
        $this->addSql('ALTER TABLE JobCategory DROP FOREIGN KEY FK_9701A2A671179CD6');
        $this->addSql('ALTER TABLE Job DROP FOREIGN KEY FK_C395A61818F45C82');
        $this->addSql('ALTER TABLE Job DROP FOREIGN KEY FK_C395A61864D218E');
        $this->addSql('ALTER TABLE Job DROP FOREIGN KEY FK_C395A618D9F966B');
        $this->addSql('ALTER TABLE Job DROP FOREIGN KEY FK_C395A618464E68B');
        $this->addSql('ALTER TABLE Job DROP FOREIGN KEY FK_C395A61871179CD6');
        $this->addSql('ALTER TABLE JobLabel DROP FOREIGN KEY FK_ECF91BE071179CD6');
        $this->addSql('ALTER TABLE JobLabel DROP FOREIGN KEY FK_ECF91BE0BF69284D');
        $this->addSql('ALTER TABLE CompanyLocalisedText RENAME TO CareerLocalisedText');
        $this->addSql('ALTER TABLE Company ADD CONSTRAINT FK_800230D326C79F4B FOREIGN KEY (slogan_id) REFERENCES CareerLocalisedText (id)');
        $this->addSql('ALTER TABLE Company ADD CONSTRAINT FK_800230D3D9F966B FOREIGN KEY (description_id) REFERENCES CareerLocalisedText (id)');
        $this->addSql('ALTER TABLE Company ADD CONSTRAINT FK_800230D318F45C82 FOREIGN KEY (website_id) REFERENCES CareerLocalisedText (id)');
        $this->addSql('ALTER TABLE CompanyPackage ADD CONSTRAINT FK_181DA5277294869C FOREIGN KEY (article_id) REFERENCES CareerLocalisedText (id)');
        $this->addSql('ALTER TABLE Job ADD CONSTRAINT FK_C395A61818F45C82 FOREIGN KEY (website_id) REFERENCES CareerLocalisedText (id)');
        $this->addSql('ALTER TABLE Job ADD CONSTRAINT FK_C395A61864D218E FOREIGN KEY (location_id) REFERENCES CareerLocalisedText (id)');
        $this->addSql('ALTER TABLE Job ADD CONSTRAINT FK_C395A618D9F966B FOREIGN KEY (description_id) REFERENCES CareerLocalisedText (id)');
        $this->addSql('ALTER TABLE Job ADD CONSTRAINT FK_C395A618464E68B FOREIGN KEY (attachment_id) REFERENCES CareerLocalisedText (id)');
        $this->addSql('ALTER TABLE Job ADD CONSTRAINT FK_C395A61871179CD6 FOREIGN KEY (name_id) REFERENCES CareerLocalisedText (id)');
        $this->addSql('ALTER TABLE JobCategory ADD CONSTRAINT FK_9701A2A6A8BEABC FOREIGN KEY (pluralName_id) REFERENCES CareerLocalisedText (id)');
        $this->addSql('ALTER TABLE JobCategory ADD CONSTRAINT FK_9701A2A6311966CE FOREIGN KEY (slug_id) REFERENCES CareerLocalisedText (id)');
        $this->addSql('ALTER TABLE JobCategory ADD CONSTRAINT FK_9701A2A671179CD6 FOREIGN KEY (name_id) REFERENCES CareerLocalisedText (id)');
        $this->addSql('ALTER TABLE JobLabel ADD CONSTRAINT FK_ECF91BE071179CD6 FOREIGN KEY (name_id) REFERENCES CareerLocalisedText (id)');
        $this->addSql('ALTER TABLE JobLabel ADD CONSTRAINT FK_ECF91BE0BF69284D FOREIGN KEY (abbreviation_id) REFERENCES CareerLocalisedText (id)');

        // Add new properties for forced logout on 1 July:
        $this->addSql('ALTER TABLE User ADD forceReloginAt DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE CompanyUser ADD forceReloginAt DATETIME DEFAULT NULL');

        // Add new things:
        $this->addSql('CREATE TABLE PasswordReset (id INT AUTO_INCREMENT NOT NULL, userType VARCHAR(255) NOT NULL, selector VARCHAR(255) NOT NULL, hashedToken VARCHAR(255) NOT NULL, expiresAt DATETIME NOT NULL, tempHash VARCHAR(255) DEFAULT NULL, tempHashExpiresAt DATETIME DEFAULT NULL, lidnr INT DEFAULT NULL, companyUser_id INT DEFAULT NULL, INDEX IDX_ED52ACECD665E01D (lidnr), INDEX IDX_ED52ACECAC7F69FF (companyUser_id), INDEX IDX_password_reset_selector (selector), INDEX IDX_password_reset_temp_hash (tempHash), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_uca1400_ai_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE PasswordReset ADD CONSTRAINT FK_ED52ACECD665E01D FOREIGN KEY (lidnr) REFERENCES Member (lidnr)');
        $this->addSql('ALTER TABLE PasswordReset ADD CONSTRAINT FK_ED52ACECAC7F69FF FOREIGN KEY (companyUser_id) REFERENCES CompanyUser (id)');
        $this->addSql('ALTER TABLE NewCompanyUser DROP FOREIGN KEY `FK_91B5005EBF396750`');
        $this->addSql('ALTER TABLE NewUser DROP FOREIGN KEY `FK_9676D507D665E01D`');
        $this->addSql('DROP TABLE NewCompanyUser');
        $this->addSql('DROP TABLE NewUser');

        $this->addSql('CREATE TABLE Session (id INT AUTO_INCREMENT NOT NULL, series VARCHAR(255) NOT NULL, hashedToken VARCHAR(255) NOT NULL, signature VARCHAR(255) NOT NULL, signaturePropertiesHash VARCHAR(255) NOT NULL, firewallName VARCHAR(255) NOT NULL, userIdentifier VARCHAR(255) NOT NULL, createdAt DATETIME NOT NULL, expiresAt DATETIME NOT NULL, lastUsedAt DATETIME NOT NULL, userAgent LONGTEXT NOT NULL, ipAddress VARCHAR(255) NOT NULL, deviceType VARCHAR(255) NOT NULL, browser VARCHAR(255) DEFAULT NULL, operatingSystem VARCHAR(255) DEFAULT NULL, phpSessionId VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_1FF9EC483A10012D (series), INDEX IDX_1FF9EC48750FAC4349EB2E5 (userIdentifier, firewallName), INDEX IDX_1FF9EC483A10012D (series), INDEX IDX_1FF9EC482B8C7D2F (expiresAt), INDEX IDX_1FF9EC481E699685 (phpSessionId), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_uca1400_ai_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE CompanyUser ADD totpSecret LONGTEXT DEFAULT NULL, ADD backupCodes LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE User ADD totpSecret LONGTEXT DEFAULT NULL, ADD backupCodes LONGTEXT DEFAULT NULL');

        // Remove some typing comments from Doctrine 2:
        $this->addSql('ALTER TABLE ApiApp CHANGE claims claims LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE Signup CHANGE drawn drawn TINYINT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException();
    }
}
