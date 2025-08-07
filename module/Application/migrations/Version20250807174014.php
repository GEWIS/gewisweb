<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * phpcs:disable Generic.Files.LineLength.TooLong
 * phpcs:disable SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall
 */
final class Version20250807174014 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Update mailing list definitions from GEWISDB.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE MailingListMember (email VARCHAR(255) NOT NULL, member INT NOT NULL, mailingList VARCHAR(255) NOT NULL, INDEX IDX_3A8467A97B1AC3ED (mailingList), INDEX IDX_3A8467A970E4FA78 (member), UNIQUE INDEX mailinglistmember_unique_idx (mailingList, member, email), PRIMARY KEY(mailingList, member, email)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE MailingListMember ADD CONSTRAINT FK_3A8467A97B1AC3ED FOREIGN KEY (mailingList) REFERENCES MailingList (name)');
        $this->addSql('ALTER TABLE MailingListMember ADD CONSTRAINT FK_3A8467A970E4FA78 FOREIGN KEY (member) REFERENCES Member (lidnr)');
        $this->addSql('ALTER TABLE members_mailinglists DROP FOREIGN KEY FK_5AD357D95E237E06');
        $this->addSql('ALTER TABLE members_mailinglists DROP FOREIGN KEY FK_5AD357D9D665E01D');
        $this->addSql('DROP TABLE members_mailinglists');
        $this->addSql('ALTER TABLE MailingList DROP onForm, DROP defaultSub');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TABLE members_mailinglists (lidnr INT NOT NULL, name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, INDEX IDX_5AD357D95E237E06 (name), INDEX IDX_5AD357D9D665E01D (lidnr), PRIMARY KEY(lidnr, name)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE members_mailinglists ADD CONSTRAINT FK_5AD357D95E237E06 FOREIGN KEY (name) REFERENCES MailingList (name)');
        $this->addSql('ALTER TABLE members_mailinglists ADD CONSTRAINT FK_5AD357D9D665E01D FOREIGN KEY (lidnr) REFERENCES Member (lidnr)');
        $this->addSql('ALTER TABLE MailingListMember DROP FOREIGN KEY FK_3A8467A97B1AC3ED');
        $this->addSql('ALTER TABLE MailingListMember DROP FOREIGN KEY FK_3A8467A970E4FA78');
        $this->addSql('DROP TABLE MailingListMember');
        $this->addSql('ALTER TABLE MailingList ADD onForm TINYINT(1) NOT NULL, ADD defaultSub TINYINT(1) NOT NULL');
    }
}
