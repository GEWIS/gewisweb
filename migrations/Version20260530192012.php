<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Move the organising organ, the organising company and the labels from the `Activity` onto the `ActivityRevision`. The
 * same applies to labels from `Vacancy` to `VacancyRevision`.
 *
 * Each revision carries its own copy, so the existing values are backfilled onto every revision before the originals
 * are dropped.
 *
 * phpcs:disable Generic.Files.LineLength.TooLong
 * phpcs:disable SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall
 */
final class Version20260530192012 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Move organ/company/labels onto the activity revision and labels onto the vacancy revision.';
    }

    public function up(Schema $schema): void
    {
        // Add label assignment revision tables and add organ and company to a revision.
        $this->addSql('CREATE TABLE ActivityRevisionLabelAssignment (activityrevision_id INT NOT NULL, activitylabel_id INT NOT NULL, INDEX IDX_AD4B45A22B53B2FF (activityrevision_id), INDEX IDX_AD4B45A247A3B8A4 (activitylabel_id), PRIMARY KEY (activityrevision_id, activitylabel_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE ActivityRevisionLabelAssignment ADD CONSTRAINT FK_AD4B45A22B53B2FF FOREIGN KEY (activityrevision_id) REFERENCES ActivityRevision (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE ActivityRevisionLabelAssignment ADD CONSTRAINT FK_AD4B45A247A3B8A4 FOREIGN KEY (activitylabel_id) REFERENCES ActivityLabel (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE ActivityRevision ADD organ_id INT DEFAULT NULL, ADD company_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE ActivityRevision ADD CONSTRAINT FK_F7309B7AE4445171 FOREIGN KEY (organ_id) REFERENCES Organ (id)');
        $this->addSql('ALTER TABLE ActivityRevision ADD CONSTRAINT FK_F7309B7A979B1AD6 FOREIGN KEY (company_id) REFERENCES Company (id)');
        $this->addSql('CREATE INDEX IDX_F7309B7AE4445171 ON ActivityRevision (organ_id)');
        $this->addSql('CREATE INDEX IDX_F7309B7A979B1AD6 ON ActivityRevision (company_id)');

        // Backfill every revision with its activity's organ/company and label assignments (labels were never versioned,
        // so copying onto every revision keeps a revision and its predecessor in sync).
        $this->addSql(<<<'SQL'
            UPDATE ActivityRevision r
            JOIN Activity a ON r.activity_id = a.id
            SET r.organ_id = a.organ_id,
                r.company_id = a.company_id
        SQL);
        $this->addSql(<<<'SQL'
            INSERT INTO ActivityRevisionLabelAssignment (activityrevision_id, activitylabel_id)
            SELECT r.id, la.activitylabel_id
            FROM ActivityRevision r
            JOIN ActivityLabelAssignment la ON la.activity_id = r.activity_id
        SQL);

        // Drop the original labels on the activity.
        $this->addSql('ALTER TABLE ActivityLabelAssignment DROP FOREIGN KEY `FK_131965B847A3B8A4`');
        $this->addSql('ALTER TABLE ActivityLabelAssignment DROP FOREIGN KEY `FK_131965B881C06096`');
        $this->addSql('DROP TABLE ActivityLabelAssignment');
        $this->addSql('ALTER TABLE Activity DROP FOREIGN KEY `FK_55026B0C979B1AD6`');
        $this->addSql('ALTER TABLE Activity DROP FOREIGN KEY `FK_55026B0CE4445171`');
        $this->addSql('DROP INDEX IDX_55026B0CE4445171 ON Activity');
        $this->addSql('DROP INDEX IDX_55026B0C979B1AD6 ON Activity');
        $this->addSql('ALTER TABLE Activity DROP organ_id, DROP company_id');

        // Vacancy labels: the same move but onto VacancyRevision.
        $this->addSql('CREATE TABLE VacancyRevisionLabelAssignment (vacancyrevision_id INT NOT NULL, vacancylabel_id INT NOT NULL, INDEX IDX_E72E458B84E1C68C (vacancyrevision_id), INDEX IDX_E72E458BD0807282 (vacancylabel_id), PRIMARY KEY (vacancyrevision_id, vacancylabel_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE VacancyRevisionLabelAssignment ADD CONSTRAINT FK_E72E458B84E1C68C FOREIGN KEY (vacancyrevision_id) REFERENCES VacancyRevision (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE VacancyRevisionLabelAssignment ADD CONSTRAINT FK_E72E458BD0807282 FOREIGN KEY (vacancylabel_id) REFERENCES VacancyLabel (id) ON DELETE CASCADE');
        $this->addSql(<<<'SQL'
            INSERT INTO VacancyRevisionLabelAssignment (vacancyrevision_id, vacancylabel_id)
            SELECT r.id, la.vacancylabel_id
            FROM VacancyRevision r
            JOIN VacancyLabelAssignment la ON la.vacancy_id = r.vacancy_id
        SQL);
        $this->addSql('ALTER TABLE VacancyLabelAssignment DROP FOREIGN KEY `FK_238B465E433B78C4`');
        $this->addSql('ALTER TABLE VacancyLabelAssignment DROP FOREIGN KEY `FK_238B465ED0807282`');
        $this->addSql('DROP TABLE VacancyLabelAssignment');
    }

    public function down(Schema $schema): void
    {
        // Vacancy labels: restore the table, backfill from the display revision, drop the new table.
        $this->addSql('CREATE TABLE VacancyLabelAssignment (vacancy_id INT NOT NULL, vacancylabel_id INT NOT NULL, INDEX IDX_238B465E433B78C4 (vacancy_id), INDEX IDX_238B465ED0807282 (vacancylabel_id), PRIMARY KEY (vacancy_id, vacancylabel_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_uca1400_ai_ci` ENGINE = InnoDB COMMENT = \'\'');
        $this->addSql('ALTER TABLE VacancyLabelAssignment ADD CONSTRAINT `FK_238B465E433B78C4` FOREIGN KEY (vacancy_id) REFERENCES Vacancy (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE VacancyLabelAssignment ADD CONSTRAINT `FK_238B465ED0807282` FOREIGN KEY (vacancylabel_id) REFERENCES VacancyLabel (id) ON DELETE CASCADE');
        $this->addSql(<<<'SQL'
            INSERT INTO VacancyLabelAssignment (vacancy_id, vacancylabel_id)
            SELECT v.id, ra.vacancylabel_id
            FROM Vacancy v
            JOIN VacancyRevisionLabelAssignment ra
                ON ra.vacancyrevision_id = COALESCE(v.liveRevision_id, v.currentRevision_id)
        SQL);
        $this->addSql('ALTER TABLE VacancyRevisionLabelAssignment DROP FOREIGN KEY FK_E72E458B84E1C68C');
        $this->addSql('ALTER TABLE VacancyRevisionLabelAssignment DROP FOREIGN KEY FK_E72E458BD0807282');
        $this->addSql('DROP TABLE VacancyRevisionLabelAssignment');

        // Do the same, but for activities.
        $this->addSql('CREATE TABLE ActivityLabelAssignment (activity_id INT NOT NULL, activitylabel_id INT NOT NULL, INDEX IDX_131965B847A3B8A4 (activitylabel_id), INDEX IDX_131965B881C06096 (activity_id), PRIMARY KEY (activity_id, activitylabel_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_uca1400_ai_ci` ENGINE = InnoDB COMMENT = \'\'');
        $this->addSql('ALTER TABLE ActivityLabelAssignment ADD CONSTRAINT `FK_131965B847A3B8A4` FOREIGN KEY (activitylabel_id) REFERENCES ActivityLabel (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE ActivityLabelAssignment ADD CONSTRAINT `FK_131965B881C06096` FOREIGN KEY (activity_id) REFERENCES Activity (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE Activity ADD organ_id INT DEFAULT NULL, ADD company_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE Activity ADD CONSTRAINT `FK_55026B0C979B1AD6` FOREIGN KEY (company_id) REFERENCES Company (id)');
        $this->addSql('ALTER TABLE Activity ADD CONSTRAINT `FK_55026B0CE4445171` FOREIGN KEY (organ_id) REFERENCES Organ (id)');
        $this->addSql('CREATE INDEX IDX_55026B0CE4445171 ON Activity (organ_id)');
        $this->addSql('CREATE INDEX IDX_55026B0C979B1AD6 ON Activity (company_id)');

        // Backfill the activity from its display revision (the live one, else the working head).
        $this->addSql(<<<'SQL'
            UPDATE Activity a
            JOIN ActivityRevision r ON r.id = COALESCE(a.liveRevision_id, a.currentRevision_id)
            SET a.organ_id = r.organ_id,
                a.company_id = r.company_id
        SQL);
        $this->addSql(<<<'SQL'
            INSERT INTO ActivityLabelAssignment (activity_id, activitylabel_id)
            SELECT a.id, ra.activitylabel_id
            FROM Activity a
            JOIN ActivityRevisionLabelAssignment ra
                ON ra.activityrevision_id = COALESCE(a.liveRevision_id, a.currentRevision_id)
        SQL);

        // Drop the revision-scoped columns/table.
        $this->addSql('ALTER TABLE ActivityRevisionLabelAssignment DROP FOREIGN KEY FK_AD4B45A22B53B2FF');
        $this->addSql('ALTER TABLE ActivityRevisionLabelAssignment DROP FOREIGN KEY FK_AD4B45A247A3B8A4');
        $this->addSql('DROP TABLE ActivityRevisionLabelAssignment');
        $this->addSql('ALTER TABLE ActivityRevision DROP FOREIGN KEY FK_F7309B7AE4445171');
        $this->addSql('ALTER TABLE ActivityRevision DROP FOREIGN KEY FK_F7309B7A979B1AD6');
        $this->addSql('DROP INDEX IDX_F7309B7AE4445171 ON ActivityRevision');
        $this->addSql('DROP INDEX IDX_F7309B7A979B1AD6 ON ActivityRevision');
        $this->addSql('ALTER TABLE ActivityRevision DROP organ_id, DROP company_id');
    }
}
