<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

use function sprintf;

/**
 * phpcs:disable Generic.Files.LineLength.TooLong
 * phpcs:disable SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall
 */
final class Version20260802133000 extends AbstractMigration
{
    /**
     * Lower-cases, replaces anything outside the allowed set with a hyphen, collapses runs of hyphens, drops whatever
     * precedes the first letter, and trims stray hyphens off both ends.
     */
    private const string NORMALIZE = "TRIM(BOTH '-' FROM REGEXP_REPLACE(REGEXP_REPLACE(REGEXP_REPLACE(LOWER(TRIM(%s)), '[^0-9a-z_-]+', '-'), '-{2,}', '-'), '^[^a-z]+', ''))";

    public function getDescription(): string
    {
        return 'Bring every company and vacancy slug into the shape the public URLs assume: lower-case, starting with a letter, and otherwise only letters, digits, underscores and hyphens. Company slugs become unique at the database level; a vacancy slug only has to be unique within its company and category, which is not a shape a single index can hold, so it is settled here and by the forms.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE Company SET slugName = ' . $this->normalize('slugName'));
        // Whatever was left of a slug made entirely of unusable characters gets a name it can at least be reached by.
        $this->addSql("UPDATE Company SET slugName = CONCAT('company-', id) WHERE slugName = ''");
        $this->addSql('UPDATE Company c INNER JOIN (SELECT id, ROW_NUMBER() OVER (PARTITION BY slugName ORDER BY id) AS rn FROM Company) d ON d.id = c.id SET c.slugName = CONCAT(c.slugName, \'-\', d.rn) WHERE d.rn > 1');
        // Suffixing can land on a slug that was already taken, so anything still doubled falls back to its id.
        $this->addSql('UPDATE Company c INNER JOIN (SELECT id, slugName FROM Company) o ON o.slugName = c.slugName AND o.id < c.id SET c.slugName = CONCAT(c.slugName, \'-\', c.id)');
        $this->addSql('CREATE UNIQUE INDEX company_slug_uniq ON Company (slugName)');

        $this->addSql('UPDATE Vacancy SET slugName = ' . $this->normalize('slugName'));
        $this->addSql("UPDATE Vacancy SET slugName = CONCAT('vacancy-', id) WHERE slugName = ''");
        // A vacancy is reached through its company and the category of the revision on show, so that is the scope its
        // slug has to be unique in.
        $this->addSql(
            'UPDATE Vacancy v INNER JOIN (SELECT v2.id, ROW_NUMBER() OVER ('
            . 'PARTITION BY p.company_id, r.category, v2.slugName ORDER BY v2.id'
            . ') AS rn FROM Vacancy v2'
            . ' INNER JOIN CompanyPackage p ON p.id = v2.package_id'
            . ' LEFT JOIN VacancyRevision r ON r.id = COALESCE(v2.liveRevision_id, v2.currentRevision_id)'
            . ') d ON d.id = v.id SET v.slugName = CONCAT(v.slugName, \'-\', d.rn) WHERE d.rn > 1',
        );
    }

    public function down(Schema $schema): void
    {
        // The slugs the normalisation replaced are not kept, so only the constraint comes off again.
        $this->addSql('DROP INDEX company_slug_uniq ON Company');
    }

    private function normalize(string $column): string
    {
        return sprintf(
            self::NORMALIZE,
            $column,
        );
    }
}
