<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * phpcs:disable Generic.Files.LineLength.TooLong
 * phpcs:disable SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall
 */
final class Version20260606184222 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Record when a sign-up list draw was performed and by which board member (the draw lock + audit).';
    }

    public function up(Schema $schema): void
    {
        // drawnAt (non-null = the draw is locked) and drawnBy (the board member who performed it).
        $this->addSql('ALTER TABLE SignupList ADD drawnAt DATETIME DEFAULT NULL, ADD drawnBy_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE SignupList ADD CONSTRAINT FK_274D085F4FA7FF98 FOREIGN KEY (drawnBy_id) REFERENCES Member (lidnr)');
        $this->addSql('CREATE INDEX IDX_274D085F4FA7FF98 ON SignupList (drawnBy_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE SignupList DROP FOREIGN KEY FK_274D085F4FA7FF98');
        $this->addSql('DROP INDEX IDX_274D085F4FA7FF98 ON SignupList');
        $this->addSql('ALTER TABLE SignupList DROP drawnAt, DROP drawnBy_id');
    }
}
