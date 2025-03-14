<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * phpcs:disable Generic.Files.LineLength.TooLong
 * phpcs:disable SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall
 */
final class Version20250304130147 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Allow tagging of BM/GMM bodies in photos.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP INDEX tag_idx ON Tag');
        $this->addSql('ALTER TABLE Tag ADD body_id INT DEFAULT NULL, ADD type VARCHAR(255) NOT NULL, CHANGE member_id member_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE Tag ADD CONSTRAINT FK_3BC4F1639B621D84 FOREIGN KEY (body_id) REFERENCES Organ (id)');
        $this->addSql('CREATE INDEX IDX_3BC4F1639B621D84 ON Tag (body_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE Tag DROP FOREIGN KEY FK_3BC4F1639B621D84');
        $this->addSql('DROP INDEX IDX_3BC4F1639B621D84 ON Tag');
        $this->addSql('ALTER TABLE Tag DROP body_id, DROP type, CHANGE member_id member_id INT NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX tag_idx ON Tag (photo_id, member_id)');
    }
}
