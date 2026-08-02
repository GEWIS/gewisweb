<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * phpcs:disable Generic.Files.LineLength.TooLong
 * phpcs:disable SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall
 */
final class Version20260802124249 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Record a coarse administrative timeline per company: who was invited, what happened to the packages and the banner, who the primary contact is. What changed inside the profile and the vacancies stays with the revision chain, which already keeps that in full.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE CompanyAuditLog (verb ENUM(\'company_created\', \'representative_invited\', \'invite_resent\', \'invite_revoked\', \'representative_joined\', \'representative_disabled\', \'representative_enabled\', \'representative_removed\', \'primary_contact_changed\', \'package_created\', \'package_updated\', \'package_deleted\', \'banner_proposed\', \'banner_approved\', \'banner_rejected\', \'banner_replaced\', \'highlight_selection_changed\', \'logo_uploaded\') NOT NULL, detail VARCHAR(255) NOT NULL, createdAt DATETIME NOT NULL, id INT AUTO_INCREMENT NOT NULL, company_id INT NOT NULL, actor INT DEFAULT NULL, actorCompanyUser INT DEFAULT NULL, INDEX IDX_49B3BF2E979B1AD6 (company_id), INDEX IDX_49B3BF2E447556F9 (actor), INDEX IDX_49B3BF2EB3C28E59 (actorCompanyUser), INDEX company_audit_log_created_idx (createdAt), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE CompanyAuditLog ADD CONSTRAINT FK_49B3BF2E979B1AD6 FOREIGN KEY (company_id) REFERENCES Company (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE CompanyAuditLog ADD CONSTRAINT FK_49B3BF2E447556F9 FOREIGN KEY (actor) REFERENCES User (lidnr) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE CompanyAuditLog ADD CONSTRAINT FK_49B3BF2EB3C28E59 FOREIGN KEY (actorCompanyUser) REFERENCES CompanyUser (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE CompanyAuditLog DROP FOREIGN KEY FK_49B3BF2E979B1AD6');
        $this->addSql('ALTER TABLE CompanyAuditLog DROP FOREIGN KEY FK_49B3BF2E447556F9');
        $this->addSql('ALTER TABLE CompanyAuditLog DROP FOREIGN KEY FK_49B3BF2EB3C28E59');
        $this->addSql('DROP TABLE CompanyAuditLog');
    }
}
