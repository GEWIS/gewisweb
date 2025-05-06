<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * phpcs:disable Generic.Files.LineLength.TooLong
 * phpcs:disable SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall
 */
final class Version20250506195523 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fix broken CourseDocuments after application Language refactor (Version20250423132841).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE `CourseDocument` SET `language`=\'english_greatbritain\' WHERE `language` = \'en\'');
        $this->addSql('UPDATE `CourseDocument` SET `language`=\'dutch_netherlands\' WHERE `language` = \'nl\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('UPDATE `CourseDocument` SET `language`=\'en\' WHERE `language` = \'english_greatbritain\'');
        $this->addSql('UPDATE `CourseDocument` SET `language`=\'nl\' WHERE `language` = \'dutch_netherlands\'');
    }
}
