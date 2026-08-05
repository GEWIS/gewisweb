<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * phpcs:disable Generic.Files.LineLength.TooLong
 * phpcs:disable SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall
 */
final class Version20260802113543 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Give a company user its own name, email address and company reference instead of borrowing the company\'s representative details through a shared primary key, so a company can have more than one of them, and let a representative who has moved on be shut out without losing the trail they left. Removing a company user now nulls out the revisions and comments it left behind and takes its password resets and notifications with it. A company points at the one representative the board writes to instead of keeping a name and an email address that belonged to nobody in particular, and a pending invitation to represent a company is held as a split-token link that lasts a week, because no account can exist before its holder has chosen a password.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE CompanyUser DROP FOREIGN KEY `FK_E2A56B32BF396750`');
        // Add the new columns as nullable so the existing rows survive, backfill them from the company that used to
        // own the representative details, and only then tighten them up.
        $this->addSql('ALTER TABLE CompanyUser ADD email VARCHAR(255) DEFAULT NULL, ADD name VARCHAR(255) DEFAULT NULL, ADD disabledAt DATETIME DEFAULT NULL, ADD createdAt DATETIME DEFAULT NULL, ADD updatedAt DATETIME DEFAULT NULL, ADD company_id INT DEFAULT NULL');
        $this->addSql('UPDATE CompanyUser cu INNER JOIN Company c ON c.id = cu.id SET cu.company_id = c.id, cu.email = c.representativeEmail, cu.name = c.representativeName, cu.createdAt = NOW(), cu.updatedAt = NOW()');
        // A representative address was never unique, so an agency or a shared mailbox could stand for several
        // companies. An address is what a representative signs in with from here on, so the later ones get a plus tag:
        // still the same mailbox, so a password reset reaches the person, but an address of their own.
        $this->addSql('UPDATE CompanyUser cu INNER JOIN (SELECT id, ROW_NUMBER() OVER (PARTITION BY email ORDER BY id) AS rn FROM CompanyUser) d ON d.id = cu.id SET cu.email = CONCAT(SUBSTRING_INDEX(cu.email, \'@\', 1), \'+\', cu.company_id, \'@\', SUBSTRING_INDEX(cu.email, \'@\', -1)) WHERE d.rn > 1');
        // Tagging can land on an address that was already taken, so anything still doubled falls back to its id.
        $this->addSql('UPDATE CompanyUser cu INNER JOIN (SELECT id, email FROM CompanyUser) o ON o.email = cu.email AND o.id < cu.id SET cu.email = CONCAT(cu.id, \'-\', cu.email)');
        $this->addSql('ALTER TABLE CompanyUser CHANGE email email VARCHAR(255) NOT NULL, CHANGE name name VARCHAR(255) NOT NULL, CHANGE createdAt createdAt DATETIME NOT NULL, CHANGE updatedAt updatedAt DATETIME NOT NULL, CHANGE company_id company_id INT NOT NULL, CHANGE id id INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE CompanyUser ADD CONSTRAINT FK_E2A56B32979B1AD6 FOREIGN KEY (company_id) REFERENCES Company (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_E2A56B32979B1AD6 ON CompanyUser (company_id)');
        $this->addSql('CREATE UNIQUE INDEX company_user_email_uniq ON CompanyUser (email)');

        $this->addSql('ALTER TABLE ActivityRevision DROP FOREIGN KEY `FK_F7309B7A102DD120`');
        $this->addSql('ALTER TABLE ActivityRevision DROP FOREIGN KEY `FK_F7309B7AFD16CEE4`');
        $this->addSql('ALTER TABLE ActivityRevision ADD CONSTRAINT FK_F7309B7A102DD120 FOREIGN KEY (lastEditedByCompanyUser_id) REFERENCES CompanyUser (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE ActivityRevision ADD CONSTRAINT FK_F7309B7AFD16CEE4 FOREIGN KEY (authorCompanyUser_id) REFERENCES CompanyUser (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE ActivityRevisionComment DROP FOREIGN KEY `FK_DEE0948DFD16CEE4`');
        $this->addSql('ALTER TABLE ActivityRevisionComment ADD CONSTRAINT FK_DEE0948DFD16CEE4 FOREIGN KEY (authorCompanyUser_id) REFERENCES CompanyUser (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE CompanyRevision DROP FOREIGN KEY `FK_48CAB2AE102DD120`');
        $this->addSql('ALTER TABLE CompanyRevision DROP FOREIGN KEY `FK_48CAB2AEFD16CEE4`');
        $this->addSql('ALTER TABLE CompanyRevision ADD CONSTRAINT FK_48CAB2AE102DD120 FOREIGN KEY (lastEditedByCompanyUser_id) REFERENCES CompanyUser (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE CompanyRevision ADD CONSTRAINT FK_48CAB2AEFD16CEE4 FOREIGN KEY (authorCompanyUser_id) REFERENCES CompanyUser (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE CompanyRevisionComment DROP FOREIGN KEY `FK_E65AF115FD16CEE4`');
        $this->addSql('ALTER TABLE CompanyRevisionComment ADD CONSTRAINT FK_E65AF115FD16CEE4 FOREIGN KEY (authorCompanyUser_id) REFERENCES CompanyUser (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE VacancyRevision DROP FOREIGN KEY `FK_FFE914BF102DD120`');
        $this->addSql('ALTER TABLE VacancyRevision DROP FOREIGN KEY `FK_FFE914BFFD16CEE4`');
        $this->addSql('ALTER TABLE VacancyRevision ADD CONSTRAINT FK_FFE914BF102DD120 FOREIGN KEY (lastEditedByCompanyUser_id) REFERENCES CompanyUser (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE VacancyRevision ADD CONSTRAINT FK_FFE914BFFD16CEE4 FOREIGN KEY (authorCompanyUser_id) REFERENCES CompanyUser (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE VacancyRevisionComment DROP FOREIGN KEY `FK_EE72B76BFD16CEE4`');
        $this->addSql('ALTER TABLE VacancyRevisionComment ADD CONSTRAINT FK_EE72B76BFD16CEE4 FOREIGN KEY (authorCompanyUser_id) REFERENCES CompanyUser (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE EditLock DROP FOREIGN KEY `FK_5EF688A7B7C41E8`');
        $this->addSql('ALTER TABLE EditLock ADD CONSTRAINT FK_5EF688A7B7C41E8 FOREIGN KEY (lockedByCompanyUser_id) REFERENCES CompanyUser (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE PasswordReset DROP FOREIGN KEY `FK_ED52ACECAC7F69FF`');
        $this->addSql('ALTER TABLE PasswordReset ADD CONSTRAINT FK_ED52ACECAC7F69FF FOREIGN KEY (companyUser_id) REFERENCES CompanyUser (id) ON DELETE CASCADE');

        $this->addSql('ALTER TABLE Company ADD primaryContact_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE Company ADD CONSTRAINT FK_800230D32FFB3A60 FOREIGN KEY (primaryContact_id) REFERENCES CompanyUser (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_800230D32FFB3A60 ON Company (primaryContact_id)');
        // Every company still has exactly the one account the representative details were migrated into, so that is
        // the contact. Matching on the address would miss the ones whose address had to be tagged to stay unique.
        $this->addSql('UPDATE Company c INNER JOIN CompanyUser cu ON cu.company_id = c.id SET c.primaryContact_id = cu.id');
        $this->addSql('ALTER TABLE Company DROP representativeName, DROP representativeEmail');

        $this->addSql('CREATE TABLE CompanyUserInvite (email VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, id INT AUTO_INCREMENT NOT NULL, selector VARCHAR(255) NOT NULL, hashedToken VARCHAR(255) NOT NULL, expiresAt DATETIME NOT NULL, createdAt DATETIME NOT NULL, updatedAt DATETIME NOT NULL, company_id INT NOT NULL, invitedBy INT DEFAULT NULL, INDEX IDX_B7CD18E2979B1AD6 (company_id), INDEX IDX_B7CD18E2D709EC86 (invitedBy), INDEX IDX_company_user_invite_selector (selector), UNIQUE INDEX company_user_invite_email_uniq (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE CompanyUserInvite ADD CONSTRAINT FK_B7CD18E2979B1AD6 FOREIGN KEY (company_id) REFERENCES Company (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE CompanyUserInvite ADD CONSTRAINT FK_B7CD18E2D709EC86 FOREIGN KEY (invitedBy) REFERENCES User (lidnr) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE CompanyUserInvite DROP FOREIGN KEY FK_B7CD18E2979B1AD6');
        $this->addSql('ALTER TABLE CompanyUserInvite DROP FOREIGN KEY FK_B7CD18E2D709EC86');
        $this->addSql('DROP TABLE CompanyUserInvite');

        $this->addSql('ALTER TABLE Company ADD representativeName VARCHAR(255) DEFAULT NULL, ADD representativeEmail VARCHAR(255) DEFAULT NULL');
        $this->addSql('UPDATE Company c INNER JOIN CompanyUser cu ON cu.id = c.primaryContact_id SET c.representativeName = cu.name, c.representativeEmail = cu.email');
        // A company that never had a contact has nothing to restore them from, and the columns did not allow that.
        $this->addSql('UPDATE Company SET representativeName = \'\', representativeEmail = \'\' WHERE representativeName IS NULL');
        $this->addSql('ALTER TABLE Company CHANGE representativeName representativeName VARCHAR(255) NOT NULL, CHANGE representativeEmail representativeEmail VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE Company DROP FOREIGN KEY FK_800230D32FFB3A60');
        $this->addSql('DROP INDEX IDX_800230D32FFB3A60 ON Company');
        $this->addSql('ALTER TABLE Company DROP primaryContact_id');

        // Only the account that shares its id with a company can go back to the shared primary key; any additional
        // representative a company gained since is dropped while the lenient foreign keys are still in place.
        $this->addSql('DELETE cu FROM CompanyUser cu LEFT JOIN Company c ON c.id = cu.id WHERE c.id IS NULL OR cu.company_id <> cu.id');

        $this->addSql('ALTER TABLE ActivityRevision DROP FOREIGN KEY FK_F7309B7AFD16CEE4');
        $this->addSql('ALTER TABLE ActivityRevision DROP FOREIGN KEY FK_F7309B7A102DD120');
        $this->addSql('ALTER TABLE ActivityRevision ADD CONSTRAINT `FK_F7309B7AFD16CEE4` FOREIGN KEY (authorCompanyUser_id) REFERENCES CompanyUser (id)');
        $this->addSql('ALTER TABLE ActivityRevision ADD CONSTRAINT `FK_F7309B7A102DD120` FOREIGN KEY (lastEditedByCompanyUser_id) REFERENCES CompanyUser (id)');
        $this->addSql('ALTER TABLE ActivityRevisionComment DROP FOREIGN KEY FK_DEE0948DFD16CEE4');
        $this->addSql('ALTER TABLE ActivityRevisionComment ADD CONSTRAINT `FK_DEE0948DFD16CEE4` FOREIGN KEY (authorCompanyUser_id) REFERENCES CompanyUser (id)');
        $this->addSql('ALTER TABLE CompanyRevision DROP FOREIGN KEY FK_48CAB2AEFD16CEE4');
        $this->addSql('ALTER TABLE CompanyRevision DROP FOREIGN KEY FK_48CAB2AE102DD120');
        $this->addSql('ALTER TABLE CompanyRevision ADD CONSTRAINT `FK_48CAB2AEFD16CEE4` FOREIGN KEY (authorCompanyUser_id) REFERENCES CompanyUser (id)');
        $this->addSql('ALTER TABLE CompanyRevision ADD CONSTRAINT `FK_48CAB2AE102DD120` FOREIGN KEY (lastEditedByCompanyUser_id) REFERENCES CompanyUser (id)');
        $this->addSql('ALTER TABLE CompanyRevisionComment DROP FOREIGN KEY FK_E65AF115FD16CEE4');
        $this->addSql('ALTER TABLE CompanyRevisionComment ADD CONSTRAINT `FK_E65AF115FD16CEE4` FOREIGN KEY (authorCompanyUser_id) REFERENCES CompanyUser (id)');
        $this->addSql('ALTER TABLE VacancyRevision DROP FOREIGN KEY FK_FFE914BFFD16CEE4');
        $this->addSql('ALTER TABLE VacancyRevision DROP FOREIGN KEY FK_FFE914BF102DD120');
        $this->addSql('ALTER TABLE VacancyRevision ADD CONSTRAINT `FK_FFE914BFFD16CEE4` FOREIGN KEY (authorCompanyUser_id) REFERENCES CompanyUser (id)');
        $this->addSql('ALTER TABLE VacancyRevision ADD CONSTRAINT `FK_FFE914BF102DD120` FOREIGN KEY (lastEditedByCompanyUser_id) REFERENCES CompanyUser (id)');
        $this->addSql('ALTER TABLE VacancyRevisionComment DROP FOREIGN KEY FK_EE72B76BFD16CEE4');
        $this->addSql('ALTER TABLE VacancyRevisionComment ADD CONSTRAINT `FK_EE72B76BFD16CEE4` FOREIGN KEY (authorCompanyUser_id) REFERENCES CompanyUser (id)');
        $this->addSql('ALTER TABLE EditLock DROP FOREIGN KEY FK_5EF688A7B7C41E8');
        $this->addSql('ALTER TABLE EditLock ADD CONSTRAINT `FK_5EF688A7B7C41E8` FOREIGN KEY (lockedByCompanyUser_id) REFERENCES CompanyUser (id)');
        $this->addSql('ALTER TABLE PasswordReset DROP FOREIGN KEY FK_ED52ACECAC7F69FF');
        $this->addSql('ALTER TABLE PasswordReset ADD CONSTRAINT `FK_ED52ACECAC7F69FF` FOREIGN KEY (companyUser_id) REFERENCES CompanyUser (id)');

        $this->addSql('ALTER TABLE CompanyUser DROP FOREIGN KEY FK_E2A56B32979B1AD6');
        $this->addSql('DROP INDEX IDX_E2A56B32979B1AD6 ON CompanyUser');
        $this->addSql('DROP INDEX company_user_email_uniq ON CompanyUser');
        $this->addSql('ALTER TABLE CompanyUser DROP email, DROP name, DROP disabledAt, DROP createdAt, DROP updatedAt, DROP company_id, CHANGE id id INT NOT NULL');
        $this->addSql('ALTER TABLE CompanyUser ADD CONSTRAINT `FK_E2A56B32BF396750` FOREIGN KEY (id) REFERENCES Company (id)');
    }
}
