<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * phpcs:disable Generic.Files.LineLength.TooLong
 * phpcs:disable SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall
 */
final class Version20260528090453 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Activity categories are actually activity labels; preparation for GH-2052.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE ActivityLabelAssignment (activity_id INT NOT NULL, activitylabel_id INT NOT NULL, INDEX IDX_131965B881C06096 (activity_id), INDEX IDX_131965B847A3B8A4 (activitylabel_id), PRIMARY KEY (activity_id, activitylabel_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE ActivityLabel (id INT AUTO_INCREMENT NOT NULL, name_id INT NOT NULL, UNIQUE INDEX UNIQ_22F99F9071179CD6 (name_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE ActivityLabelAssignment ADD CONSTRAINT FK_131965B881C06096 FOREIGN KEY (activity_id) REFERENCES Activity (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE ActivityLabelAssignment ADD CONSTRAINT FK_131965B847A3B8A4 FOREIGN KEY (activitylabel_id) REFERENCES ActivityLabel (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE ActivityLabel ADD CONSTRAINT FK_22F99F9071179CD6 FOREIGN KEY (name_id) REFERENCES ActivityLocalisedText (id)');
        $this->addSql('INSERT INTO ActivityLabel (id, name_id) SELECT id, name_id FROM ActivityCategory');
        $this->addSql('INSERT INTO ActivityLabelAssignment (activity_id, activitylabel_id) SELECT activity_id, activitycategory_id FROM ActivityCategoryAssignment');
        $this->addSql('ALTER TABLE ActivityCategory DROP FOREIGN KEY `FK_9C1F977771179CD6`');
        $this->addSql('ALTER TABLE ActivityCategoryAssignment DROP FOREIGN KEY `FK_480AC9B324EF5392`');
        $this->addSql('ALTER TABLE ActivityCategoryAssignment DROP FOREIGN KEY `FK_480AC9B381C06096`');
        $this->addSql('DROP TABLE ActivityCategory');
        $this->addSql('DROP TABLE ActivityCategoryAssignment');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TABLE ActivityCategory (id INT AUTO_INCREMENT NOT NULL, name_id INT NOT NULL, UNIQUE INDEX UNIQ_9C1F977771179CD6 (name_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_uca1400_ai_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE ActivityCategoryAssignment (activity_id INT NOT NULL, activitycategory_id INT NOT NULL, INDEX IDX_480AC9B381C06096 (activity_id), INDEX IDX_480AC9B324EF5392 (activitycategory_id), PRIMARY KEY (activity_id, activitycategory_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_uca1400_ai_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE ActivityCategory ADD CONSTRAINT `FK_9C1F977771179CD6` FOREIGN KEY (name_id) REFERENCES ActivityLocalisedText (id)');
        $this->addSql('ALTER TABLE ActivityCategoryAssignment ADD CONSTRAINT `FK_480AC9B324EF5392` FOREIGN KEY (activitycategory_id) REFERENCES ActivityCategory (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE ActivityCategoryAssignment ADD CONSTRAINT `FK_480AC9B381C06096` FOREIGN KEY (activity_id) REFERENCES Activity (id) ON DELETE CASCADE');
        $this->addSql('INSERT INTO ActivityCategory (id, name_id) SELECT id, name_id FROM ActivityLabel');
        $this->addSql('INSERT INTO ActivityCategoryAssignment (activity_id, activitycategory_id) SELECT activity_id, activitylabel_id FROM ActivityLabelAssignment');
        $this->addSql('ALTER TABLE ActivityLabelAssignment DROP FOREIGN KEY FK_131965B881C06096');
        $this->addSql('ALTER TABLE ActivityLabelAssignment DROP FOREIGN KEY FK_131965B847A3B8A4');
        $this->addSql('ALTER TABLE ActivityLabel DROP FOREIGN KEY FK_22F99F9071179CD6');
        $this->addSql('DROP TABLE ActivityLabelAssignment');
        $this->addSql('DROP TABLE ActivityLabel');
    }
}
