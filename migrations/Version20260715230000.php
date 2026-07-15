<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * phpcs:disable Generic.Files.LineLength.TooLong
 * phpcs:disable SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall
 */
final class Version20260715230000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Let members hide the photos they are tagged in from their own photo page.';
    }

    public function up(Schema $schema): void
    {
        // How much of a member's tagged-photo collection is hidden from others; a missing settings row means "none".
        $this->addSql("ALTER TABLE UserSettings ADD photoVisibility VARCHAR(255) DEFAULT 'none' NOT NULL");

        // The photos a member hid, keyed by the (member, photo) pair rather than the tag, so re-tagging cannot unhide.
        $this->addSql('CREATE TABLE HiddenPhoto (id INT AUTO_INCREMENT NOT NULL, member_id INT NOT NULL, photo_id INT NOT NULL, INDEX IDX_HiddenPhoto_Photo (photo_id), UNIQUE INDEX hidden_photo_uniq (member_id, photo_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE HiddenPhoto ADD CONSTRAINT FK_HiddenPhoto_Member FOREIGN KEY (member_id) REFERENCES Member (lidnr) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE HiddenPhoto ADD CONSTRAINT FK_HiddenPhoto_Photo FOREIGN KEY (photo_id) REFERENCES Photo (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE HiddenPhoto DROP FOREIGN KEY FK_HiddenPhoto_Member');
        $this->addSql('ALTER TABLE HiddenPhoto DROP FOREIGN KEY FK_HiddenPhoto_Photo');
        $this->addSql('DROP TABLE HiddenPhoto');
        $this->addSql('ALTER TABLE UserSettings DROP photoVisibility');
    }
}
