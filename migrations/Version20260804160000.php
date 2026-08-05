<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * phpcs:disable Generic.Files.LineLength.TooLong
 * phpcs:disable SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall
 */
final class Version20260804160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop the abbreviation of a vacancy label. Nothing ever showed it: a label is one or two words and every surface that draws one draws its name, so a second translated string only meant more to fill in when adding a label.';
    }

    public function up(Schema $schema): void
    {
        // The constraint goes first so the texts it points at can be deleted while the column is still there to find
        // them by; the column itself goes last.
        $this->addSql('ALTER TABLE VacancyLabel DROP FOREIGN KEY FK_ECF91BE0BF69284D');
        $this->addSql('DROP INDEX UNIQ_12576AE6BF69284D ON VacancyLabel');
        $this->addSql('DELETE t FROM CareerLocalisedText t INNER JOIN VacancyLabel l ON l.abbreviation_id = t.id');
        $this->addSql('ALTER TABLE VacancyLabel DROP abbreviation_id');
    }

    public function down(Schema $schema): void
    {
        // What the abbreviations said is gone, so each label gets its name back as one: the column does not allow null
        // and a label without an abbreviation would fail the form that required both languages of it. The marker
        // column is how each fresh copy finds its way back to the label it was made for.
        $this->addSql('ALTER TABLE VacancyLabel ADD abbreviation_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE CareerLocalisedText ADD restoredForLabel INT DEFAULT NULL');
        $this->addSql('INSERT INTO CareerLocalisedText (valueEN, valueNL, restoredForLabel) SELECT t.valueEN, t.valueNL, l.id FROM VacancyLabel l INNER JOIN CareerLocalisedText t ON t.id = l.name_id');
        $this->addSql('UPDATE VacancyLabel l SET l.abbreviation_id = (SELECT t.id FROM CareerLocalisedText t WHERE t.restoredForLabel = l.id)');
        $this->addSql('ALTER TABLE CareerLocalisedText DROP restoredForLabel');

        $this->addSql('ALTER TABLE VacancyLabel CHANGE abbreviation_id abbreviation_id INT NOT NULL');
        $this->addSql('ALTER TABLE VacancyLabel ADD CONSTRAINT FK_ECF91BE0BF69284D FOREIGN KEY (abbreviation_id) REFERENCES CareerLocalisedText (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_12576AE6BF69284D ON VacancyLabel (abbreviation_id)');
    }
}
