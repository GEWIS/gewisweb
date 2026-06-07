<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * A revision comment's author is the authenticated principal who wrote it, and this is not even necessarily a member.
 * Re-point each comment table's `author_id` from `Member` to `User` (both keyed on `lidnr`, so existing values stay
 * valid. Makes it nullable and adds a nullable `authorCompanyUser_id` so a company user can author careers comments.
 *
 * phpcs:disable Generic.Files.LineLength.TooLong
 * phpcs:disable SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall
 */
final class Version20260530194212 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Make revision comment authors users (or company users) instead of members.';
    }

    public function up(Schema $schema): void
    {
        // Re-point author_id (Member -> User), make it nullable, and add the company-user author column to each
        // concrete revision-comment table (the fields live on the AbstractRevisionComment mapped superclass).
        $this->addSql('ALTER TABLE ActivityRevisionComment DROP FOREIGN KEY `FK_DEE0948DF675F31B`');
        $this->addSql('ALTER TABLE ActivityRevisionComment ADD authorCompanyUser_id INT DEFAULT NULL, CHANGE author_id author_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE ActivityRevisionComment ADD CONSTRAINT FK_DEE0948DF675F31B FOREIGN KEY (author_id) REFERENCES User (lidnr)');
        $this->addSql('ALTER TABLE ActivityRevisionComment ADD CONSTRAINT FK_DEE0948DFD16CEE4 FOREIGN KEY (authorCompanyUser_id) REFERENCES CompanyUser (id)');
        $this->addSql('CREATE INDEX IDX_DEE0948DFD16CEE4 ON ActivityRevisionComment (authorCompanyUser_id)');
        $this->addSql('ALTER TABLE CompanyRevisionComment DROP FOREIGN KEY `FK_E65AF115F675F31B`');
        $this->addSql('ALTER TABLE CompanyRevisionComment ADD authorCompanyUser_id INT DEFAULT NULL, CHANGE author_id author_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE CompanyRevisionComment ADD CONSTRAINT FK_E65AF115F675F31B FOREIGN KEY (author_id) REFERENCES User (lidnr)');
        $this->addSql('ALTER TABLE CompanyRevisionComment ADD CONSTRAINT FK_E65AF115FD16CEE4 FOREIGN KEY (authorCompanyUser_id) REFERENCES CompanyUser (id)');
        $this->addSql('CREATE INDEX IDX_E65AF115FD16CEE4 ON CompanyRevisionComment (authorCompanyUser_id)');
        $this->addSql('ALTER TABLE VacancyRevisionComment DROP FOREIGN KEY `FK_EE72B76BF675F31B`');
        $this->addSql('ALTER TABLE VacancyRevisionComment ADD authorCompanyUser_id INT DEFAULT NULL, CHANGE author_id author_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE VacancyRevisionComment ADD CONSTRAINT FK_EE72B76BF675F31B FOREIGN KEY (author_id) REFERENCES User (lidnr)');
        $this->addSql('ALTER TABLE VacancyRevisionComment ADD CONSTRAINT FK_EE72B76BFD16CEE4 FOREIGN KEY (authorCompanyUser_id) REFERENCES CompanyUser (id)');
        $this->addSql('CREATE INDEX IDX_EE72B76BFD16CEE4 ON VacancyRevisionComment (authorCompanyUser_id)');
    }

    public function down(Schema $schema): void
    {
        // Restore the company-user-less, member-anchored author on each comment table.
        $this->addSql('ALTER TABLE ActivityRevisionComment DROP FOREIGN KEY FK_DEE0948DF675F31B');
        $this->addSql('ALTER TABLE ActivityRevisionComment DROP FOREIGN KEY FK_DEE0948DFD16CEE4');
        $this->addSql('DROP INDEX IDX_DEE0948DFD16CEE4 ON ActivityRevisionComment');
        $this->addSql('ALTER TABLE ActivityRevisionComment DROP authorCompanyUser_id, CHANGE author_id author_id INT NOT NULL');
        $this->addSql('ALTER TABLE ActivityRevisionComment ADD CONSTRAINT `FK_DEE0948DF675F31B` FOREIGN KEY (author_id) REFERENCES Member (lidnr)');
        $this->addSql('ALTER TABLE CompanyRevisionComment DROP FOREIGN KEY FK_E65AF115F675F31B');
        $this->addSql('ALTER TABLE CompanyRevisionComment DROP FOREIGN KEY FK_E65AF115FD16CEE4');
        $this->addSql('DROP INDEX IDX_E65AF115FD16CEE4 ON CompanyRevisionComment');
        $this->addSql('ALTER TABLE CompanyRevisionComment DROP authorCompanyUser_id, CHANGE author_id author_id INT NOT NULL');
        $this->addSql('ALTER TABLE CompanyRevisionComment ADD CONSTRAINT `FK_E65AF115F675F31B` FOREIGN KEY (author_id) REFERENCES Member (lidnr)');
        $this->addSql('ALTER TABLE VacancyRevisionComment DROP FOREIGN KEY FK_EE72B76BF675F31B');
        $this->addSql('ALTER TABLE VacancyRevisionComment DROP FOREIGN KEY FK_EE72B76BFD16CEE4');
        $this->addSql('DROP INDEX IDX_EE72B76BFD16CEE4 ON VacancyRevisionComment');
        $this->addSql('ALTER TABLE VacancyRevisionComment DROP authorCompanyUser_id, CHANGE author_id author_id INT NOT NULL');
        $this->addSql('ALTER TABLE VacancyRevisionComment ADD CONSTRAINT `FK_EE72B76BF675F31B` FOREIGN KEY (author_id) REFERENCES Member (lidnr)');
    }
}
