<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * phpcs:disable Generic.Files.LineLength.TooLong
 * phpcs:disable SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall
 */
final class Version20260710070912 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Replace the VacancyCategory entity with a hard-coded VacancyCategories enum; GH-2068.';
    }

    public function up(Schema $schema): void
    {
        // Store the category as its enum value (a plain string) on the revision, instead of a foreign key.
        $this->addSql('ALTER TABLE VacancyRevision ADD category VARCHAR(255) DEFAULT NULL');

        // Best-effort backfill from the old free-form categories: map by the (lowercased) English slug where it lines
        // up with one of the new fixed categories, otherwise fall back to `jobs`.
        $this->addSql(<<<'SQL'
            UPDATE VacancyRevision vr
            INNER JOIN VacancyCategory vc ON vr.category_id = vc.id
            INNER JOIN CareerLocalisedText slug ON slug.id = vc.slug_id
            SET vr.category = CASE LOWER(slug.valueEN)
                WHEN 'jobs' THEN 'jobs'
                WHEN 'internships' THEN 'internships'
                WHEN 'traineeships' THEN 'traineeships'
                WHEN 'student-jobs' THEN 'student-jobs'
                WHEN 'thesis-projects' THEN 'thesis-projects'
                ELSE 'jobs'
            END
            SQL);
        $this->addSql('UPDATE VacancyRevision SET category = \'jobs\' WHERE category IS NULL');
        $this->addSql('ALTER TABLE VacancyRevision CHANGE category category VARCHAR(255) NOT NULL');

        // Drop the now-unused foreign key, index and column.
        $this->addSql('ALTER TABLE VacancyRevision DROP FOREIGN KEY FK_FFE914BF12469DE2');
        $this->addSql('DROP INDEX IDX_FFE914BF12469DE2 ON VacancyRevision');
        $this->addSql('ALTER TABLE VacancyRevision DROP category_id');

        // Remove the category table and the localised texts it owned. The localised texts are orphan-removing in the
        // ORM, but that is not a database cascade, so clean them up here (FK checks off to sidestep ordering).
        $this->addSql('SET FOREIGN_KEY_CHECKS = 0');
        $this->addSql(<<<'SQL'
            DELETE clt FROM CareerLocalisedText clt
            INNER JOIN VacancyCategory vc ON clt.id IN (vc.name_id, vc.pluralName_id, vc.slug_id)
            SQL);
        $this->addSql('DROP TABLE VacancyCategory');
        $this->addSql('SET FOREIGN_KEY_CHECKS = 1');
    }

    public function down(Schema $schema): void
    {
        // Recreate the category table and its localised-text foreign keys.
        $this->addSql('CREATE TABLE VacancyCategory (id INT AUTO_INCREMENT NOT NULL, name_id INT NOT NULL, slug_id INT NOT NULL, hidden TINYINT(1) NOT NULL, pluralName_id INT NOT NULL, UNIQUE INDEX UNIQ_94C618B271179CD6 (name_id), UNIQUE INDEX UNIQ_94C618B2A8BEABC (pluralName_id), UNIQUE INDEX UNIQ_94C618B2311966CE (slug_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE VacancyCategory ADD CONSTRAINT FK_9701A2A671179CD6 FOREIGN KEY (name_id) REFERENCES CareerLocalisedText (id)');
        $this->addSql('ALTER TABLE VacancyCategory ADD CONSTRAINT FK_9701A2A6A8BEABC FOREIGN KEY (pluralName_id) REFERENCES CareerLocalisedText (id)');
        $this->addSql('ALTER TABLE VacancyCategory ADD CONSTRAINT FK_9701A2A6311966CE FOREIGN KEY (slug_id) REFERENCES CareerLocalisedText (id)');

        // Add the foreign key column back (nullable for now, so we can backfill).
        $this->addSql('ALTER TABLE VacancyRevision ADD category_id INT DEFAULT NULL');

        // Reconstitute the fixed categories as rows so existing revisions can point at them again. The original
        // free-form names are unrecoverable, so we recreate them from the enum (Dutch labels are best-effort).
        $this->reconstituteCategory('jobs', 'Job', 'Baan', 'Jobs', 'Banen');
        $this->reconstituteCategory('internships', 'Internship', 'Stage', 'Internships', 'Stages');
        $this->reconstituteCategory('traineeships', 'Traineeship', 'Traineeship', 'Traineeships', 'Traineeships');
        $this->reconstituteCategory('student-jobs', 'Student job', 'Studentenbaan', 'Student jobs', 'Studentenbanen');
        $this->reconstituteCategory('thesis-projects', 'Thesis project', 'Afstudeerproject', 'Thesis projects', 'Afstudeerprojecten');

        // Any revision whose category value is unknown falls back to `jobs`.
        $this->addSql('UPDATE VacancyRevision vr INNER JOIN VacancyCategory vc ON vc.id = (SELECT vc2.id FROM VacancyCategory vc2 INNER JOIN CareerLocalisedText s ON s.id = vc2.slug_id WHERE s.valueEN = \'jobs\' LIMIT 1) SET vr.category_id = vc.id WHERE vr.category_id IS NULL');

        $this->addSql('ALTER TABLE VacancyRevision CHANGE category_id category_id INT NOT NULL');
        $this->addSql('ALTER TABLE VacancyRevision ADD CONSTRAINT FK_FFE914BF12469DE2 FOREIGN KEY (category_id) REFERENCES VacancyCategory (id)');
        $this->addSql('CREATE INDEX IDX_FFE914BF12469DE2 ON VacancyRevision (category_id)');
        $this->addSql('ALTER TABLE VacancyRevision DROP category');
    }

    /**
     * Recreates a single {@see \App\Entity\Career\VacancyCategory} row (with its three localised texts) and repoints
     * every revision whose enum value matches $slug back at it. Uses session variables, which survive the implicit
     * commits of the surrounding statements on the same connection.
     */
    private function reconstituteCategory(
        string $slug,
        string $nameEn,
        string $nameNl,
        string $pluralEn,
        string $pluralNl,
    ): void {
        $this->addSql('INSERT INTO CareerLocalisedText (valueEN, valueNL) VALUES (:en, :nl)', ['en' => $nameEn, 'nl' => $nameNl]);
        $this->addSql('SET @name_id = LAST_INSERT_ID()');
        $this->addSql('INSERT INTO CareerLocalisedText (valueEN, valueNL) VALUES (:en, :nl)', ['en' => $pluralEn, 'nl' => $pluralNl]);
        $this->addSql('SET @plural_id = LAST_INSERT_ID()');
        $this->addSql('INSERT INTO CareerLocalisedText (valueEN, valueNL) VALUES (:slug, :slug)', ['slug' => $slug]);
        $this->addSql('SET @slug_id = LAST_INSERT_ID()');
        $this->addSql('INSERT INTO VacancyCategory (name_id, pluralName_id, slug_id, hidden) VALUES (@name_id, @plural_id, @slug_id, 0)');
        $this->addSql('UPDATE VacancyRevision SET category_id = LAST_INSERT_ID() WHERE category = :slug', ['slug' => $slug]);
    }
}
