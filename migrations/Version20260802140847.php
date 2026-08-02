<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * phpcs:disable Generic.Files.LineLength.TooLong
 * phpcs:disable SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall
 */
final class Version20260802140847 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Give a vacancy a posting window. The closing day is required, since a company knows when applications close before it knows anything else and a posting nobody has to put an end to quietly goes stale; the opening day is optional and means the vacancy appears as soon as it is approved. The window sits on the revision, so moving it is reviewed like the rest of the content and a vacancy disappears on its own once the day passes. Add the highlight package, which puts a handful of a company\'s own vacancies on the career landing page as a plain many-to-many, so a vacancy that closes or is taken down drops out of the highlights on its own. Record the size a banner package was bought in, and let it hold a proposal alongside the banner that is live, because a company shows its banner to everyone who visits the site and whatever is already up stays up until the committee agrees to a replacement.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE VacancyRevision ADD startDate DATE DEFAULT NULL, ADD endDate DATE DEFAULT NULL');
        // Nothing said when an existing vacancy should close, so it runs as long as the package it was sold under,
        // which is the ceiling it was subject to all along.
        $this->addSql('UPDATE VacancyRevision r INNER JOIN Vacancy v ON v.id = r.vacancy_id INNER JOIN CompanyPackage p ON p.id = v.package_id SET r.endDate = p.expires');
        $this->addSql('ALTER TABLE VacancyRevision CHANGE endDate endDate DATE NOT NULL');

        $this->addSql('CREATE TABLE CompanyHighlightPackageVacancy (companyhighlightpackage_id INT NOT NULL, vacancy_id INT NOT NULL, INDEX IDX_48FA547F8C97BFF1 (companyhighlightpackage_id), INDEX IDX_48FA547F433B78C4 (vacancy_id), PRIMARY KEY (companyhighlightpackage_id, vacancy_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE CompanyHighlightPackageVacancy ADD CONSTRAINT FK_48FA547F8C97BFF1 FOREIGN KEY (companyhighlightpackage_id) REFERENCES CompanyPackage (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE CompanyHighlightPackageVacancy ADD CONSTRAINT FK_48FA547F433B78C4 FOREIGN KEY (vacancy_id) REFERENCES Vacancy (id) ON DELETE CASCADE');

        $this->addSql('ALTER TABLE CompanyPackage ADD format VARCHAR(255) DEFAULT NULL, ADD pendingImage VARCHAR(255) DEFAULT NULL, ADD pendingImageSubmittedAt DATETIME DEFAULT NULL, ADD pendingImageSubmittedBy_id INT DEFAULT NULL');
        // Everything sold up to now was the narrow strip, so that is what the banners already running are.
        $this->addSql('UPDATE CompanyPackage SET format = \'leaderboard\' WHERE packageType = \'banner\'');
        $this->addSql('ALTER TABLE CompanyPackage ADD CONSTRAINT FK_181DA5271E68BA3B FOREIGN KEY (pendingImageSubmittedBy_id) REFERENCES CompanyUser (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_181DA5271E68BA3B ON CompanyPackage (pendingImageSubmittedBy_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE CompanyPackage DROP FOREIGN KEY FK_181DA5271E68BA3B');
        $this->addSql('DROP INDEX IDX_181DA5271E68BA3B ON CompanyPackage');
        $this->addSql('ALTER TABLE CompanyPackage DROP format, DROP pendingImage, DROP pendingImageSubmittedAt, DROP pendingImageSubmittedBy_id');

        $this->addSql('ALTER TABLE CompanyHighlightPackageVacancy DROP FOREIGN KEY FK_48FA547F8C97BFF1');
        $this->addSql('ALTER TABLE CompanyHighlightPackageVacancy DROP FOREIGN KEY FK_48FA547F433B78C4');
        $this->addSql('DROP TABLE CompanyHighlightPackageVacancy');

        $this->addSql('ALTER TABLE VacancyRevision DROP startDate, DROP endDate');
    }
}
