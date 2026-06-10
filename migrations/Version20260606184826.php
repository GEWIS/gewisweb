<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add the allocation method (and its per-method settings) to a sign-up list (GH-1799 foundation).
 *
 * phpcs:disable Generic.Files.LineLength.TooLong
 * phpcs:disable SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall
 */
final class Version20260606184826 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add allocationMethod and its per-method settings to SignupList.';
    }

    public function up(Schema $schema): void
    {
        // Add the columns nullable, backfill existing rows with the defaults, then enforce NOT NULL where required, so
        // the change is safe on a table that already holds sign-up lists.
        $this->addSql('ALTER TABLE SignupList ADD allocationMethod VARCHAR(255) DEFAULT NULL, ADD drawCutoffRule VARCHAR(255) DEFAULT NULL, ADD drawCutoffAt DATETIME DEFAULT NULL, ADD drawAfterDurationHours INT DEFAULT NULL, ADD externalPolicyUrl VARCHAR(255) DEFAULT NULL, ADD externalForceOrdering TINYINT DEFAULT NULL, ADD externalPaymentByExternal TINYINT DEFAULT NULL, ADD customMethodDescription LONGTEXT DEFAULT NULL');
        $this->addSql('UPDATE SignupList SET allocationMethod = \'first-come-first-served\', externalForceOrdering = 0, externalPaymentByExternal = 0');
        $this->addSql('ALTER TABLE SignupList CHANGE allocationMethod allocationMethod VARCHAR(255) NOT NULL, CHANGE externalForceOrdering externalForceOrdering TINYINT NOT NULL, CHANGE externalPaymentByExternal externalPaymentByExternal TINYINT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE SignupList DROP allocationMethod, DROP drawCutoffRule, DROP drawCutoffAt, DROP drawAfterDurationHours, DROP externalPolicyUrl, DROP externalForceOrdering, DROP externalPaymentByExternal, DROP customMethodDescription');
    }
}
