<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260607180918 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add external sign-up email verification tokens and a policy-agreement timestamp on sign-ups.';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE ExternalSignupVerification (purpose VARCHAR(255) NOT NULL, selector VARCHAR(255) NOT NULL, hashedToken VARCHAR(255) NOT NULL, expiresAt DATETIME NOT NULL, id INT AUTO_INCREMENT NOT NULL, external_signup_id INT NOT NULL, INDEX IDX_D55257277B3A307A (external_signup_id), INDEX IDX_external_signup_verification_selector (selector), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE ExternalSignupVerification ADD CONSTRAINT FK_D55257277B3A307A FOREIGN KEY (external_signup_id) REFERENCES Signup (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE Signup ADD agreedToPolicyAt DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ExternalSignupVerification DROP FOREIGN KEY FK_D55257277B3A307A');
        $this->addSql('DROP TABLE ExternalSignupVerification');
        $this->addSql('ALTER TABLE Signup DROP agreedToPolicyAt');
    }
}
