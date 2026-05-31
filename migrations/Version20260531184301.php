<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add the generic `EditLock` table (heartbeat-kept exclusive edit lock on a revisable aggregate, keyed by `resourceId`
 * and `resourceKey`) and, on every revision table, an optimistic-locking `version` column plus the `lastEditedBy`/
 * `lastEditedByCompanyUser` audit columns. New columns are nullable (or default 1), so no backfill is needed.
 *
 * phpcs:disable Generic.Files.LineLength.TooLong
 * phpcs:disable SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall
 */
final class Version20260531184301 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add the EditLock table and version + last-editor columns to the revision tables.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE EditLock (resourceId VARCHAR(32) NOT NULL, resourceKey INT NOT NULL, acquiredAt DATETIME NOT NULL, lastPingAt DATETIME NOT NULL, id INT AUTO_INCREMENT NOT NULL, lockedBy_id INT DEFAULT NULL, lockedByCompanyUser_id INT DEFAULT NULL, INDEX IDX_5EF688A71E253D71 (lockedBy_id), INDEX IDX_5EF688A7B7C41E8 (lockedByCompanyUser_id), UNIQUE INDEX edit_lock_resource_uniq (resourceId, resourceKey), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4");
        $this->addSql("ALTER TABLE EditLock ADD CONSTRAINT FK_5EF688A71E253D71 FOREIGN KEY (lockedBy_id) REFERENCES User (lidnr)");
        $this->addSql("ALTER TABLE EditLock ADD CONSTRAINT FK_5EF688A7B7C41E8 FOREIGN KEY (lockedByCompanyUser_id) REFERENCES CompanyUser (id)");
        $this->addSql("ALTER TABLE ActivityRevision ADD version INT DEFAULT 1 NOT NULL, ADD lastEditedBy_id INT DEFAULT NULL, ADD lastEditedByCompanyUser_id INT DEFAULT NULL");
        $this->addSql("ALTER TABLE ActivityRevision ADD CONSTRAINT FK_F7309B7AA19E445F FOREIGN KEY (lastEditedBy_id) REFERENCES User (lidnr)");
        $this->addSql("ALTER TABLE ActivityRevision ADD CONSTRAINT FK_F7309B7A102DD120 FOREIGN KEY (lastEditedByCompanyUser_id) REFERENCES CompanyUser (id)");
        $this->addSql('CREATE INDEX IDX_F7309B7AA19E445F ON ActivityRevision (lastEditedBy_id)');
        $this->addSql('CREATE INDEX IDX_F7309B7A102DD120 ON ActivityRevision (lastEditedByCompanyUser_id)');
        $this->addSql("ALTER TABLE CompanyRevision ADD version INT DEFAULT 1 NOT NULL, ADD lastEditedBy_id INT DEFAULT NULL, ADD lastEditedByCompanyUser_id INT DEFAULT NULL");
        $this->addSql("ALTER TABLE CompanyRevision ADD CONSTRAINT FK_48CAB2AEA19E445F FOREIGN KEY (lastEditedBy_id) REFERENCES User (lidnr)");
        $this->addSql("ALTER TABLE CompanyRevision ADD CONSTRAINT FK_48CAB2AE102DD120 FOREIGN KEY (lastEditedByCompanyUser_id) REFERENCES CompanyUser (id)");
        $this->addSql('CREATE INDEX IDX_48CAB2AEA19E445F ON CompanyRevision (lastEditedBy_id)');
        $this->addSql('CREATE INDEX IDX_48CAB2AE102DD120 ON CompanyRevision (lastEditedByCompanyUser_id)');
        $this->addSql("ALTER TABLE VacancyRevision ADD version INT DEFAULT 1 NOT NULL, ADD lastEditedBy_id INT DEFAULT NULL, ADD lastEditedByCompanyUser_id INT DEFAULT NULL");
        $this->addSql("ALTER TABLE VacancyRevision ADD CONSTRAINT FK_FFE914BFA19E445F FOREIGN KEY (lastEditedBy_id) REFERENCES User (lidnr)");
        $this->addSql("ALTER TABLE VacancyRevision ADD CONSTRAINT FK_FFE914BF102DD120 FOREIGN KEY (lastEditedByCompanyUser_id) REFERENCES CompanyUser (id)");
        $this->addSql('CREATE INDEX IDX_FFE914BFA19E445F ON VacancyRevision (lastEditedBy_id)');
        $this->addSql('CREATE INDEX IDX_FFE914BF102DD120 ON VacancyRevision (lastEditedByCompanyUser_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE EditLock DROP FOREIGN KEY FK_5EF688A71E253D71');
        $this->addSql('ALTER TABLE EditLock DROP FOREIGN KEY FK_5EF688A7B7C41E8');
        $this->addSql('DROP TABLE EditLock');
        $this->addSql('ALTER TABLE ActivityRevision DROP FOREIGN KEY FK_F7309B7AA19E445F');
        $this->addSql('ALTER TABLE ActivityRevision DROP FOREIGN KEY FK_F7309B7A102DD120');
        $this->addSql('DROP INDEX IDX_F7309B7AA19E445F ON ActivityRevision');
        $this->addSql('DROP INDEX IDX_F7309B7A102DD120 ON ActivityRevision');
        $this->addSql("ALTER TABLE ActivityRevision DROP version, DROP lastEditedBy_id, DROP lastEditedByCompanyUser_id");
        $this->addSql('ALTER TABLE CompanyRevision DROP FOREIGN KEY FK_48CAB2AEA19E445F');
        $this->addSql('ALTER TABLE CompanyRevision DROP FOREIGN KEY FK_48CAB2AE102DD120');
        $this->addSql('DROP INDEX IDX_48CAB2AEA19E445F ON CompanyRevision');
        $this->addSql('DROP INDEX IDX_48CAB2AE102DD120 ON CompanyRevision');
        $this->addSql("ALTER TABLE CompanyRevision DROP version, DROP lastEditedBy_id, DROP lastEditedByCompanyUser_id");
        $this->addSql('ALTER TABLE VacancyRevision DROP FOREIGN KEY FK_FFE914BFA19E445F');
        $this->addSql('ALTER TABLE VacancyRevision DROP FOREIGN KEY FK_FFE914BF102DD120');
        $this->addSql('DROP INDEX IDX_FFE914BFA19E445F ON VacancyRevision');
        $this->addSql('DROP INDEX IDX_FFE914BF102DD120 ON VacancyRevision');
        $this->addSql("ALTER TABLE VacancyRevision DROP version, DROP lastEditedBy_id, DROP lastEditedByCompanyUser_id");
    }
}
