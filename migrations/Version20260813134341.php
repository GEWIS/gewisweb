<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * phpcs:disable Generic.Files.LineLength.TooLong
 * phpcs:disable SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall
 */
final class Version20260813134341 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Record when a custom page was written and when it was last changed. Nothing said so far, so what is already there is stamped as of now; from here on the dates say something. Bring the address of every page down to lower case as well, which is all a page address may be written in now: two addresses that differ only in their casing were two addresses for the same page, and only one of them can be the one it answers to.';
    }

    public function up(Schema $schema): void
    {
        // A default carries the existing rows over the NOT NULL and is dropped again, so the columns end up exactly as
        // the mapping describes them.
        $this->addSql(<<<'SQL'
            ALTER TABLE Page
              ADD createdAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              ADD updatedAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE Page
              MODIFY createdAt DATETIME NOT NULL,
              MODIFY updatedAt DATETIME NOT NULL
            SQL);

        // The three parts of the address, and only those: the same table holds the title and the content of a page,
        // which are prose and are left exactly as they were written.
        $this->addSql(<<<'SQL'
            UPDATE FrontpageLocalisedText t
              INNER JOIN Page p ON t.id IN (p.category_id, p.subCategory_id, p.name_id)
            SET t.valueNL = LOWER(t.valueNL),
                t.valueEN = LOWER(t.valueEN)
            SQL);
    }

    public function down(Schema $schema): void
    {
        // The columns could go, but what the casing was is not written down anywhere, so the address a page answers to
        // cannot be put back the way it was written.
        $this->throwIrreversibleMigrationException(
            'The casing a page address was written in before is not kept, so it cannot be put back.',
        );
    }
}
