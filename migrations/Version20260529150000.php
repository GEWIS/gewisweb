<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Introduces the generic revision/approval workflow and adopt it across activities and the careers portal:
 * - Splits `Activity`, `Company`, and `Vacancy` into stable entities + immutable revision chains (with comment threads)
 * - Renames the career `Job` concept to `Vacancy`
 * - Migrates the `SignupField` type to a string-backed enum
 * - Drops the superseded flat `Approvable*` model (including `ApprovableText`)
 *
 * Existing rows are migrated into a single revision-1 carrying their content; the legacy statuses map onto the new
 * `RevisionStatus`, and in-flight (never-approved) update clones are discarded.
 *
 * phpcs:disable Generic.Files.LineLength.TooLong
 * phpcs:disable SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall
 */
final class Version20260529150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Revision/approval workflow for activities + careers (split aggregates, Job->Vacancy rename, dual-actor authorship). GH-2067.';
    }

    public function up(Schema $schema): void
    {
        // Activities: split into a stable aggregate + an immutable revision chain
        $this->addSql('CREATE TABLE ActivityRevision (id INT AUTO_INCREMENT NOT NULL, status VARCHAR(255) NOT NULL, revisionNumber INT NOT NULL, reviewedAt DATETIME DEFAULT NULL, createdAt DATETIME NOT NULL, updatedAt DATETIME NOT NULL, beginTime DATETIME NOT NULL, endTime DATETIME NOT NULL, category VARCHAR(255) NOT NULL, requireGEFLITST TINYINT NOT NULL, requireZettle TINYINT NOT NULL, author_id INT DEFAULT NULL, authorCompanyUser_id INT DEFAULT NULL, reviewer_id INT DEFAULT NULL, activity_id INT NOT NULL, previousRevision_id INT DEFAULT NULL, name_id INT NOT NULL, location_id INT NOT NULL, costs_id INT NOT NULL, description_id INT NOT NULL, INDEX IDX_F7309B7AF675F31B (author_id), INDEX IDX_F7309B7AFD16CEE4 (authorCompanyUser_id), INDEX IDX_F7309B7A70574616 (reviewer_id), INDEX IDX_F7309B7A81C06096 (activity_id), INDEX IDX_F7309B7A8F2D4199 (previousRevision_id), UNIQUE INDEX UNIQ_F7309B7A71179CD6 (name_id), UNIQUE INDEX UNIQ_F7309B7A64D218E (location_id), UNIQUE INDEX UNIQ_F7309B7A27D66E0D (costs_id), UNIQUE INDEX UNIQ_F7309B7AD9F966B (description_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE ActivityRevisionComment (body LONGTEXT NOT NULL, id INT AUTO_INCREMENT NOT NULL, createdAt DATETIME NOT NULL, updatedAt DATETIME NOT NULL, author_id INT NOT NULL, revision_id INT NOT NULL, INDEX IDX_DEE0948DF675F31B (author_id), INDEX IDX_DEE0948D1DFA7C8F (revision_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE ActivityRevision ADD CONSTRAINT FK_F7309B7AF675F31B FOREIGN KEY (author_id) REFERENCES Member (lidnr)');
        $this->addSql('ALTER TABLE ActivityRevision ADD CONSTRAINT FK_F7309B7AFD16CEE4 FOREIGN KEY (authorCompanyUser_id) REFERENCES CompanyUser (id)');
        $this->addSql('ALTER TABLE ActivityRevision ADD CONSTRAINT FK_F7309B7A70574616 FOREIGN KEY (reviewer_id) REFERENCES Member (lidnr)');
        $this->addSql('ALTER TABLE ActivityRevision ADD CONSTRAINT FK_F7309B7A81C06096 FOREIGN KEY (activity_id) REFERENCES Activity (id)');
        $this->addSql('ALTER TABLE ActivityRevision ADD CONSTRAINT FK_F7309B7A8F2D4199 FOREIGN KEY (previousRevision_id) REFERENCES ActivityRevision (id)');
        $this->addSql('ALTER TABLE ActivityRevision ADD CONSTRAINT FK_F7309B7A71179CD6 FOREIGN KEY (name_id) REFERENCES ActivityLocalisedText (id)');
        $this->addSql('ALTER TABLE ActivityRevision ADD CONSTRAINT FK_F7309B7A64D218E FOREIGN KEY (location_id) REFERENCES ActivityLocalisedText (id)');
        $this->addSql('ALTER TABLE ActivityRevision ADD CONSTRAINT FK_F7309B7A27D66E0D FOREIGN KEY (costs_id) REFERENCES ActivityLocalisedText (id)');
        $this->addSql('ALTER TABLE ActivityRevision ADD CONSTRAINT FK_F7309B7AD9F966B FOREIGN KEY (description_id) REFERENCES ActivityLocalisedText (id)');
        $this->addSql('ALTER TABLE ActivityRevisionComment ADD CONSTRAINT FK_DEE0948DF675F31B FOREIGN KEY (author_id) REFERENCES Member (lidnr)');
        $this->addSql('ALTER TABLE ActivityRevisionComment ADD CONSTRAINT FK_DEE0948D1DFA7C8F FOREIGN KEY (revision_id) REFERENCES ActivityRevision (id)');
        $this->addSql('ALTER TABLE Activity ADD currentRevision_id INT DEFAULT NULL, ADD liveRevision_id INT DEFAULT NULL');
        $this->addSql(<<<'SQL'
            INSERT INTO ActivityRevision
                (status, revisionNumber, reviewedAt, createdAt, updatedAt, beginTime, endTime, category, requireGEFLITST, requireZettle, author_id, reviewer_id, activity_id, previousRevision_id, name_id, location_id, costs_id, description_id)
            SELECT
                CASE a.status WHEN 2 THEN 'approved' WHEN 3 THEN 'rejected' ELSE 'submitted' END,
                1, NULL, NOW(), NOW(), a.beginTime, a.endTime, a.category, a.requireGEFLITST, a.requireZettle,
                a.creator_id, a.approver_id, a.id, NULL, a.name_id, a.location_id, a.costs_id, a.description_id
            FROM Activity a
            WHERE a.status <> 4
            SQL);
        $this->addSql('UPDATE Activity a JOIN ActivityRevision r ON r.activity_id = a.id SET a.currentRevision_id = r.id');
        $this->addSql("UPDATE Activity a JOIN ActivityRevision r ON r.activity_id = a.id AND r.status = 'approved' SET a.liveRevision_id = r.id");
        $this->addSql('ALTER TABLE ActivityUpdateProposal DROP FOREIGN KEY `FK_9E136D5139E6FA16`');
        $this->addSql('ALTER TABLE ActivityUpdateProposal DROP FOREIGN KEY `FK_9E136D51BD06B3B3`');
        $this->addSql('DROP TABLE ActivityUpdateProposal');
        // Capture the ActivityLocalisedText rows owned by the to-be-purged status=4 update clones (and their sign-up
        // graph) BEFORE deleting their owners: the FK points owner -> text with no ON DELETE CASCADE (RESTRICT), so the
        // texts can only be deleted after their owners, by which point the *_id references are otherwise lost.
        $this->addSql('CREATE TEMPORARY TABLE _orphanedActivityText (id INT NOT NULL)');
        $this->addSql('INSERT INTO _orphanedActivityText (id) SELECT value_id FROM SignupOption WHERE field_id IN (SELECT id FROM SignupField WHERE signuplist_id IN (SELECT id FROM SignupList WHERE activity_id IN (SELECT id FROM Activity WHERE status = 4)))');
        $this->addSql('INSERT INTO _orphanedActivityText (id) SELECT name_id FROM SignupField WHERE signuplist_id IN (SELECT id FROM SignupList WHERE activity_id IN (SELECT id FROM Activity WHERE status = 4))');
        $this->addSql('INSERT INTO _orphanedActivityText (id) SELECT name_id FROM SignupList WHERE activity_id IN (SELECT id FROM Activity WHERE status = 4)');
        $this->addSql('INSERT INTO _orphanedActivityText (id) SELECT name_id FROM Activity WHERE status = 4');
        $this->addSql('INSERT INTO _orphanedActivityText (id) SELECT location_id FROM Activity WHERE status = 4');
        $this->addSql('INSERT INTO _orphanedActivityText (id) SELECT costs_id FROM Activity WHERE status = 4');
        $this->addSql('INSERT INTO _orphanedActivityText (id) SELECT description_id FROM Activity WHERE status = 4');
        $this->addSql('DELETE FROM SignupFieldValue WHERE signup_id IN (SELECT id FROM Signup WHERE signuplist_id IN (SELECT id FROM SignupList WHERE activity_id IN (SELECT id FROM Activity WHERE status = 4)))');
        $this->addSql('DELETE FROM Signup WHERE signuplist_id IN (SELECT id FROM SignupList WHERE activity_id IN (SELECT id FROM Activity WHERE status = 4))');
        $this->addSql('DELETE FROM SignupOption WHERE field_id IN (SELECT id FROM SignupField WHERE signuplist_id IN (SELECT id FROM SignupList WHERE activity_id IN (SELECT id FROM Activity WHERE status = 4)))');
        $this->addSql('DELETE FROM SignupField WHERE signuplist_id IN (SELECT id FROM SignupList WHERE activity_id IN (SELECT id FROM Activity WHERE status = 4))');
        $this->addSql('DELETE FROM SignupList WHERE activity_id IN (SELECT id FROM Activity WHERE status = 4)');
        $this->addSql('DELETE FROM ActivityLabelAssignment WHERE activity_id IN (SELECT id FROM Activity WHERE status = 4)');
        $this->addSql('DELETE FROM Activity WHERE status = 4');
        // The owners are gone; the captured texts are now orphans and can be deleted.
        $this->addSql('DELETE FROM ActivityLocalisedText WHERE id IN (SELECT id FROM _orphanedActivityText)');
        $this->addSql('DROP TEMPORARY TABLE _orphanedActivityText');
        $this->addSql('ALTER TABLE Activity DROP FOREIGN KEY `FK_55026B0C27D66E0D`');
        $this->addSql('ALTER TABLE Activity DROP FOREIGN KEY `FK_55026B0C64D218E`');
        $this->addSql('ALTER TABLE Activity DROP FOREIGN KEY `FK_55026B0C71179CD6`');
        $this->addSql('ALTER TABLE Activity DROP FOREIGN KEY `FK_55026B0CBB23766C`');
        $this->addSql('ALTER TABLE Activity DROP FOREIGN KEY `FK_55026B0CD9F966B`');
        $this->addSql('DROP INDEX IDX_55026B0CBB23766C ON Activity');
        $this->addSql('DROP INDEX UNIQ_55026B0C64D218E ON Activity');
        $this->addSql('DROP INDEX UNIQ_55026B0C27D66E0D ON Activity');
        $this->addSql('DROP INDEX UNIQ_55026B0CD9F966B ON Activity');
        $this->addSql('DROP INDEX UNIQ_55026B0C71179CD6 ON Activity');
        $this->addSql('ALTER TABLE Activity DROP name_id, DROP location_id, DROP costs_id, DROP description_id, DROP beginTime, DROP endTime, DROP status, DROP requireGEFLITST, DROP requireZettle, DROP category, DROP approver_id');
        $this->addSql('ALTER TABLE Activity ADD CONSTRAINT FK_55026B0C2796CA52 FOREIGN KEY (currentRevision_id) REFERENCES ActivityRevision (id)');
        $this->addSql('ALTER TABLE Activity ADD CONSTRAINT FK_55026B0CA892657C FOREIGN KEY (liveRevision_id) REFERENCES ActivityRevision (id)');
        $this->addSql('CREATE INDEX IDX_55026B0C2796CA52 ON Activity (currentRevision_id)');
        $this->addSql('CREATE INDEX IDX_55026B0CA892657C ON Activity (liveRevision_id)');

        // SignupField.type: integer -> string-backed enum
        $this->addSql('ALTER TABLE SignupField CHANGE type type VARCHAR(255) NOT NULL');
        $this->addSql("UPDATE SignupField SET type = CASE type WHEN '1' THEN 'yes-no' WHEN '2' THEN 'number' WHEN '3' THEN 'choice' ELSE 'text' END");

        // Career: rename Job -> Vacancy
        $this->addSql('ALTER TABLE Job DROP FOREIGN KEY FK_C395A61812469DE2');
        $this->addSql('ALTER TABLE JobLabelAssignment DROP FOREIGN KEY FK_42CB55B5BE04EA9');
        $this->addSql('ALTER TABLE JobLabelAssignment DROP FOREIGN KEY FK_42CB55B5DD1E79DF');
        $this->addSql('ALTER TABLE JobUpdate DROP FOREIGN KEY FK_961CE301108B7592');
        $this->addSql('ALTER TABLE JobUpdate DROP FOREIGN KEY FK_961CE301F4792058');
        $this->addSql('RENAME TABLE Job TO Vacancy');
        $this->addSql('RENAME TABLE JobCategory TO VacancyCategory');
        $this->addSql('RENAME TABLE JobLabel TO VacancyLabel');
        $this->addSql('RENAME TABLE JobLabelAssignment TO VacancyLabelAssignment');
        $this->addSql('RENAME TABLE JobUpdate TO VacancyUpdate');
        $this->addSql('ALTER TABLE VacancyLabelAssignment CHANGE job_id vacancy_id INT NOT NULL');
        $this->addSql('ALTER TABLE VacancyLabelAssignment CHANGE joblabel_id vacancylabel_id INT NOT NULL');
        $this->addSql('ALTER TABLE Vacancy RENAME INDEX UNIQ_C395A61871179CD6 TO UNIQ_6689552171179CD6');
        $this->addSql('ALTER TABLE Vacancy RENAME INDEX UNIQ_C395A61864D218E TO UNIQ_6689552164D218E');
        $this->addSql('ALTER TABLE Vacancy RENAME INDEX UNIQ_C395A61818F45C82 TO UNIQ_6689552118F45C82');
        $this->addSql('ALTER TABLE Vacancy RENAME INDEX UNIQ_C395A618D9F966B TO UNIQ_66895521D9F966B');
        $this->addSql('ALTER TABLE Vacancy RENAME INDEX UNIQ_C395A618464E68B TO UNIQ_66895521464E68B');
        $this->addSql('ALTER TABLE Vacancy RENAME INDEX UNIQ_C395A618502BCAA2 TO UNIQ_66895521502BCAA2');
        $this->addSql('ALTER TABLE Vacancy RENAME INDEX IDX_C395A618F44CABFF TO IDX_66895521F44CABFF');
        $this->addSql('ALTER TABLE Vacancy RENAME INDEX IDX_C395A61812469DE2 TO IDX_6689552112469DE2');
        $this->addSql('ALTER TABLE Vacancy RENAME INDEX IDX_C395A618BB23766C TO IDX_66895521BB23766C');
        $this->addSql('ALTER TABLE VacancyCategory RENAME INDEX UNIQ_9701A2A671179CD6 TO UNIQ_94C618B271179CD6');
        $this->addSql('ALTER TABLE VacancyCategory RENAME INDEX UNIQ_9701A2A6A8BEABC TO UNIQ_94C618B2A8BEABC');
        $this->addSql('ALTER TABLE VacancyCategory RENAME INDEX UNIQ_9701A2A6311966CE TO UNIQ_94C618B2311966CE');
        $this->addSql('ALTER TABLE VacancyLabel RENAME INDEX UNIQ_ECF91BE071179CD6 TO UNIQ_12576AE671179CD6');
        $this->addSql('ALTER TABLE VacancyLabel RENAME INDEX UNIQ_ECF91BE0BF69284D TO UNIQ_12576AE6BF69284D');
        $this->addSql('ALTER TABLE VacancyUpdate RENAME INDEX UNIQ_961CE301F4792058 TO UNIQ_7F81E845F4792058');
        $this->addSql('ALTER TABLE VacancyUpdate RENAME INDEX IDX_961CE301108B7592 TO IDX_7F81E845108B7592');
        $this->addSql('ALTER TABLE VacancyLabelAssignment RENAME INDEX IDX_42CB55B5BE04EA9 TO IDX_238B465E433B78C4');
        $this->addSql('ALTER TABLE VacancyLabelAssignment RENAME INDEX IDX_42CB55B5DD1E79DF TO IDX_238B465ED0807282');
        $this->addSql('ALTER TABLE Vacancy ADD CONSTRAINT FK_6689552112469DE2 FOREIGN KEY (category_id) REFERENCES VacancyCategory (id)');
        $this->addSql('ALTER TABLE VacancyLabelAssignment ADD CONSTRAINT FK_238B465E433B78C4 FOREIGN KEY (vacancy_id) REFERENCES Vacancy (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE VacancyLabelAssignment ADD CONSTRAINT FK_238B465ED0807282 FOREIGN KEY (vacancylabel_id) REFERENCES VacancyLabel (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE VacancyUpdate ADD CONSTRAINT FK_7F81E845108B7592 FOREIGN KEY (original_id) REFERENCES Vacancy (id)');
        $this->addSql('ALTER TABLE VacancyUpdate ADD CONSTRAINT FK_7F81E845F4792058 FOREIGN KEY (proposal_id) REFERENCES Vacancy (id)');

        // Career: split Vacancy + Company into stable aggregates + revision chains
        $this->addSql('CREATE TABLE CompanyRevision (status VARCHAR(255) NOT NULL, revisionNumber INT NOT NULL, reviewedAt DATETIME DEFAULT NULL, id INT AUTO_INCREMENT NOT NULL, createdAt DATETIME NOT NULL, updatedAt DATETIME NOT NULL, logo VARCHAR(255) DEFAULT NULL, contactName VARCHAR(255) DEFAULT NULL, contactAddress VARCHAR(255) DEFAULT NULL, contactEmail VARCHAR(255) DEFAULT NULL, contactPhone VARCHAR(255) DEFAULT NULL, author_id INT DEFAULT NULL, authorCompanyUser_id INT DEFAULT NULL, reviewer_id INT DEFAULT NULL, company_id INT NOT NULL, previousRevision_id INT DEFAULT NULL, slogan_id INT NOT NULL, description_id INT NOT NULL, website_id INT NOT NULL, INDEX IDX_48CAB2AEF675F31B (author_id), INDEX IDX_48CAB2AEFD16CEE4 (authorCompanyUser_id), INDEX IDX_48CAB2AE70574616 (reviewer_id), INDEX IDX_48CAB2AE979B1AD6 (company_id), INDEX IDX_48CAB2AE8F2D4199 (previousRevision_id), UNIQUE INDEX UNIQ_48CAB2AE26C79F4B (slogan_id), UNIQUE INDEX UNIQ_48CAB2AED9F966B (description_id), UNIQUE INDEX UNIQ_48CAB2AE18F45C82 (website_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE CompanyRevisionComment (body LONGTEXT NOT NULL, id INT AUTO_INCREMENT NOT NULL, createdAt DATETIME NOT NULL, updatedAt DATETIME NOT NULL, author_id INT NOT NULL, revision_id INT NOT NULL, INDEX IDX_E65AF115F675F31B (author_id), INDEX IDX_E65AF1151DFA7C8F (revision_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE VacancyRevision (status VARCHAR(255) NOT NULL, revisionNumber INT NOT NULL, reviewedAt DATETIME DEFAULT NULL, id INT AUTO_INCREMENT NOT NULL, createdAt DATETIME NOT NULL, updatedAt DATETIME NOT NULL, contactName VARCHAR(255) DEFAULT NULL, contactPhone VARCHAR(255) DEFAULT NULL, contactEmail VARCHAR(255) DEFAULT NULL, author_id INT DEFAULT NULL, authorCompanyUser_id INT DEFAULT NULL, reviewer_id INT DEFAULT NULL, vacancy_id INT NOT NULL, previousRevision_id INT DEFAULT NULL, name_id INT NOT NULL, location_id INT NOT NULL, website_id INT NOT NULL, description_id INT NOT NULL, attachment_id INT NOT NULL, category_id INT NOT NULL, INDEX IDX_FFE914BFF675F31B (author_id), INDEX IDX_FFE914BFFD16CEE4 (authorCompanyUser_id), INDEX IDX_FFE914BF70574616 (reviewer_id), INDEX IDX_FFE914BF433B78C4 (vacancy_id), INDEX IDX_FFE914BF8F2D4199 (previousRevision_id), UNIQUE INDEX UNIQ_FFE914BF71179CD6 (name_id), UNIQUE INDEX UNIQ_FFE914BF64D218E (location_id), UNIQUE INDEX UNIQ_FFE914BF18F45C82 (website_id), UNIQUE INDEX UNIQ_FFE914BFD9F966B (description_id), UNIQUE INDEX UNIQ_FFE914BF464E68B (attachment_id), INDEX IDX_FFE914BF12469DE2 (category_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE VacancyRevisionComment (body LONGTEXT NOT NULL, id INT AUTO_INCREMENT NOT NULL, createdAt DATETIME NOT NULL, updatedAt DATETIME NOT NULL, author_id INT NOT NULL, revision_id INT NOT NULL, INDEX IDX_EE72B76BF675F31B (author_id), INDEX IDX_EE72B76B1DFA7C8F (revision_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE CompanyRevision ADD CONSTRAINT FK_48CAB2AEF675F31B FOREIGN KEY (author_id) REFERENCES Member (lidnr)');
        $this->addSql('ALTER TABLE CompanyRevision ADD CONSTRAINT FK_48CAB2AEFD16CEE4 FOREIGN KEY (authorCompanyUser_id) REFERENCES CompanyUser (id)');
        $this->addSql('ALTER TABLE CompanyRevision ADD CONSTRAINT FK_48CAB2AE70574616 FOREIGN KEY (reviewer_id) REFERENCES Member (lidnr)');
        $this->addSql('ALTER TABLE CompanyRevision ADD CONSTRAINT FK_48CAB2AE979B1AD6 FOREIGN KEY (company_id) REFERENCES Company (id)');
        $this->addSql('ALTER TABLE CompanyRevision ADD CONSTRAINT FK_48CAB2AE8F2D4199 FOREIGN KEY (previousRevision_id) REFERENCES CompanyRevision (id)');
        $this->addSql('ALTER TABLE CompanyRevision ADD CONSTRAINT FK_48CAB2AE26C79F4B FOREIGN KEY (slogan_id) REFERENCES CareerLocalisedText (id)');
        $this->addSql('ALTER TABLE CompanyRevision ADD CONSTRAINT FK_48CAB2AED9F966B FOREIGN KEY (description_id) REFERENCES CareerLocalisedText (id)');
        $this->addSql('ALTER TABLE CompanyRevision ADD CONSTRAINT FK_48CAB2AE18F45C82 FOREIGN KEY (website_id) REFERENCES CareerLocalisedText (id)');
        $this->addSql('ALTER TABLE CompanyRevisionComment ADD CONSTRAINT FK_E65AF115F675F31B FOREIGN KEY (author_id) REFERENCES Member (lidnr)');
        $this->addSql('ALTER TABLE CompanyRevisionComment ADD CONSTRAINT FK_E65AF1151DFA7C8F FOREIGN KEY (revision_id) REFERENCES CompanyRevision (id)');
        $this->addSql('ALTER TABLE VacancyRevision ADD CONSTRAINT FK_FFE914BFF675F31B FOREIGN KEY (author_id) REFERENCES Member (lidnr)');
        $this->addSql('ALTER TABLE VacancyRevision ADD CONSTRAINT FK_FFE914BFFD16CEE4 FOREIGN KEY (authorCompanyUser_id) REFERENCES CompanyUser (id)');
        $this->addSql('ALTER TABLE VacancyRevision ADD CONSTRAINT FK_FFE914BF70574616 FOREIGN KEY (reviewer_id) REFERENCES Member (lidnr)');
        $this->addSql('ALTER TABLE VacancyRevision ADD CONSTRAINT FK_FFE914BF433B78C4 FOREIGN KEY (vacancy_id) REFERENCES Vacancy (id)');
        $this->addSql('ALTER TABLE VacancyRevision ADD CONSTRAINT FK_FFE914BF8F2D4199 FOREIGN KEY (previousRevision_id) REFERENCES VacancyRevision (id)');
        $this->addSql('ALTER TABLE VacancyRevision ADD CONSTRAINT FK_FFE914BF71179CD6 FOREIGN KEY (name_id) REFERENCES CareerLocalisedText (id)');
        $this->addSql('ALTER TABLE VacancyRevision ADD CONSTRAINT FK_FFE914BF64D218E FOREIGN KEY (location_id) REFERENCES CareerLocalisedText (id)');
        $this->addSql('ALTER TABLE VacancyRevision ADD CONSTRAINT FK_FFE914BF18F45C82 FOREIGN KEY (website_id) REFERENCES CareerLocalisedText (id)');
        $this->addSql('ALTER TABLE VacancyRevision ADD CONSTRAINT FK_FFE914BFD9F966B FOREIGN KEY (description_id) REFERENCES CareerLocalisedText (id)');
        $this->addSql('ALTER TABLE VacancyRevision ADD CONSTRAINT FK_FFE914BF464E68B FOREIGN KEY (attachment_id) REFERENCES CareerLocalisedText (id)');
        $this->addSql('ALTER TABLE VacancyRevision ADD CONSTRAINT FK_FFE914BF12469DE2 FOREIGN KEY (category_id) REFERENCES VacancyCategory (id)');
        $this->addSql('ALTER TABLE VacancyRevisionComment ADD CONSTRAINT FK_EE72B76BF675F31B FOREIGN KEY (author_id) REFERENCES Member (lidnr)');
        $this->addSql('ALTER TABLE VacancyRevisionComment ADD CONSTRAINT FK_EE72B76B1DFA7C8F FOREIGN KEY (revision_id) REFERENCES VacancyRevision (id)');
        $this->addSql('ALTER TABLE Company ADD currentRevision_id INT DEFAULT NULL, ADD liveRevision_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE Vacancy ADD currentRevision_id INT DEFAULT NULL, ADD liveRevision_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE VacancyUpdate DROP FOREIGN KEY `FK_7F81E845108B7592`');
        $this->addSql('ALTER TABLE VacancyUpdate DROP FOREIGN KEY `FK_7F81E845F4792058`');
        $this->addSql('DROP TABLE VacancyUpdate');
        $this->addSql('ALTER TABLE CompanyUpdate DROP FOREIGN KEY `FK_E13E3542108B7592`');
        $this->addSql('ALTER TABLE CompanyUpdate DROP FOREIGN KEY `FK_E13E3542F4792058`');
        $this->addSql('DROP TABLE CompanyUpdate');
        $this->addSql('DELETE FROM VacancyLabelAssignment WHERE vacancy_id IN (SELECT id FROM Vacancy WHERE isUpdate = 1)');
        $this->addSql('DELETE FROM Vacancy WHERE isUpdate = 1');
        $this->addSql('DELETE FROM Company WHERE isUpdate = 1');
        $this->addSql(<<<'SQL'
            INSERT INTO CompanyRevision
                (status, revisionNumber, reviewedAt, createdAt, updatedAt, logo, contactName, contactAddress, contactEmail, contactPhone, author_id, authorCompanyUser_id, reviewer_id, company_id, previousRevision_id, slogan_id, description_id, website_id)
            SELECT
                CASE c.approved WHEN 1 THEN 'approved' WHEN 2 THEN 'rejected' ELSE 'submitted' END,
                1, c.approvedAt, NOW(), NOW(), c.logo, c.contactName, c.contactAddress, c.contactEmail, c.contactPhone,
                NULL, NULL, c.approver_id, c.id, NULL, c.slogan_id, c.description_id, c.website_id
            FROM Company c
            SQL);
        $this->addSql('UPDATE Company c JOIN CompanyRevision r ON r.company_id = c.id SET c.currentRevision_id = r.id');
        $this->addSql("UPDATE Company c JOIN CompanyRevision r ON r.company_id = c.id AND r.status = 'approved' SET c.liveRevision_id = r.id");
        $this->addSql(<<<'SQL'
            INSERT INTO VacancyRevision
                (status, revisionNumber, reviewedAt, createdAt, updatedAt, contactName, contactPhone, contactEmail, author_id, authorCompanyUser_id, reviewer_id, vacancy_id, previousRevision_id, name_id, location_id, website_id, description_id, attachment_id, category_id)
            SELECT
                CASE v.approved WHEN 1 THEN 'approved' WHEN 2 THEN 'rejected' ELSE 'submitted' END,
                1, v.approvedAt, NOW(), NOW(), v.contactName, v.contactPhone, v.contactEmail,
                NULL, NULL, v.approver_id, v.id, NULL, v.name_id, v.location_id, v.website_id, v.description_id, v.attachment_id, v.category_id
            FROM Vacancy v
            SQL);
        $this->addSql('UPDATE Vacancy v JOIN VacancyRevision r ON r.vacancy_id = v.id SET v.currentRevision_id = r.id');
        $this->addSql("UPDATE Vacancy v JOIN VacancyRevision r ON r.vacancy_id = v.id AND r.status = 'approved' SET v.liveRevision_id = r.id");
        $this->addSql('ALTER TABLE Company DROP FOREIGN KEY `FK_800230D318F45C82`');
        $this->addSql('ALTER TABLE Company DROP FOREIGN KEY `FK_800230D326C79F4B`');
        $this->addSql('ALTER TABLE Company DROP FOREIGN KEY `FK_800230D3502BCAA2`');
        $this->addSql('ALTER TABLE Company DROP FOREIGN KEY `FK_800230D3BB23766C`');
        $this->addSql('ALTER TABLE Company DROP FOREIGN KEY `FK_800230D3D9F966B`');
        $this->addSql('DROP INDEX UNIQ_800230D3502BCAA2 ON Company');
        $this->addSql('DROP INDEX UNIQ_800230D326C79F4B ON Company');
        $this->addSql('DROP INDEX IDX_800230D3BB23766C ON Company');
        $this->addSql('DROP INDEX UNIQ_800230D3D9F966B ON Company');
        $this->addSql('DROP INDEX UNIQ_800230D318F45C82 ON Company');
        $this->addSql('ALTER TABLE Company DROP slogan_id, DROP description_id, DROP website_id, DROP approver_id, DROP contactName, DROP contactAddress, DROP contactEmail, DROP contactPhone, DROP logo, DROP approved, DROP approvedAt, DROP isUpdate, DROP approvableText_id');
        $this->addSql('ALTER TABLE Vacancy DROP FOREIGN KEY `FK_6689552112469DE2`');
        $this->addSql('ALTER TABLE Vacancy DROP FOREIGN KEY `FK_C395A61818F45C82`');
        $this->addSql('ALTER TABLE Vacancy DROP FOREIGN KEY `FK_C395A618464E68B`');
        $this->addSql('ALTER TABLE Vacancy DROP FOREIGN KEY `FK_C395A618502BCAA2`');
        $this->addSql('ALTER TABLE Vacancy DROP FOREIGN KEY `FK_C395A61864D218E`');
        $this->addSql('ALTER TABLE Vacancy DROP FOREIGN KEY `FK_C395A61871179CD6`');
        $this->addSql('ALTER TABLE Vacancy DROP FOREIGN KEY `FK_C395A618BB23766C`');
        $this->addSql('ALTER TABLE Vacancy DROP FOREIGN KEY `FK_C395A618D9F966B`');
        $this->addSql('DROP INDEX UNIQ_6689552164D218E ON Vacancy');
        $this->addSql('DROP INDEX IDX_66895521BB23766C ON Vacancy');
        $this->addSql('DROP INDEX UNIQ_66895521502BCAA2 ON Vacancy');
        $this->addSql('DROP INDEX UNIQ_6689552118F45C82 ON Vacancy');
        $this->addSql('DROP INDEX UNIQ_66895521D9F966B ON Vacancy');
        $this->addSql('DROP INDEX UNIQ_6689552171179CD6 ON Vacancy');
        $this->addSql('DROP INDEX IDX_6689552112469DE2 ON Vacancy');
        $this->addSql('DROP INDEX UNIQ_66895521464E68B ON Vacancy');
        $this->addSql('ALTER TABLE Vacancy DROP name_id, DROP location_id, DROP website_id, DROP description_id, DROP attachment_id, DROP category_id, DROP approver_id, DROP contactName, DROP contactPhone, DROP contactEmail, DROP approved, DROP approvedAt, DROP isUpdate, DROP approvableText_id');
        $this->addSql('ALTER TABLE Company ADD CONSTRAINT FK_800230D32796CA52 FOREIGN KEY (currentRevision_id) REFERENCES CompanyRevision (id)');
        $this->addSql('ALTER TABLE Company ADD CONSTRAINT FK_800230D3A892657C FOREIGN KEY (liveRevision_id) REFERENCES CompanyRevision (id)');
        $this->addSql('CREATE INDEX IDX_800230D32796CA52 ON Company (currentRevision_id)');
        $this->addSql('CREATE INDEX IDX_800230D3A892657C ON Company (liveRevision_id)');
        $this->addSql('ALTER TABLE Vacancy ADD CONSTRAINT FK_668955212796CA52 FOREIGN KEY (currentRevision_id) REFERENCES VacancyRevision (id)');
        $this->addSql('ALTER TABLE Vacancy ADD CONSTRAINT FK_66895521A892657C FOREIGN KEY (liveRevision_id) REFERENCES VacancyRevision (id)');
        $this->addSql('CREATE INDEX IDX_668955212796CA52 ON Vacancy (currentRevision_id)');
        $this->addSql('CREATE INDEX IDX_66895521A892657C ON Vacancy (liveRevision_id)');

        // Drop the superseded ApprovableText table
        $this->addSql('DROP TABLE ApprovableText');
    }

    public function down(Schema $schema): void
    {
        // Recreate the ApprovableText table
        $this->addSql('CREATE TABLE ApprovableText (id INT AUTO_INCREMENT NOT NULL, message VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_uca1400_ai_ci`, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_uca1400_ai_ci` ENGINE = InnoDB COMMENT = \'\' ');

        // Career: collapse Vacancy + Company revisions back onto the aggregates
        $this->addSql('CREATE TABLE CompanyUpdate (id INT AUTO_INCREMENT NOT NULL, original_id INT NOT NULL, proposal_id INT NOT NULL, INDEX IDX_E13E3542108B7592 (original_id), UNIQUE INDEX UNIQ_E13E3542F4792058 (proposal_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_uca1400_ai_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE VacancyUpdate (id INT AUTO_INCREMENT NOT NULL, original_id INT NOT NULL, proposal_id INT NOT NULL, INDEX IDX_7F81E845108B7592 (original_id), UNIQUE INDEX UNIQ_7F81E845F4792058 (proposal_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_uca1400_ai_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE CompanyUpdate ADD CONSTRAINT `FK_E13E3542108B7592` FOREIGN KEY (original_id) REFERENCES Company (id)');
        $this->addSql('ALTER TABLE CompanyUpdate ADD CONSTRAINT `FK_E13E3542F4792058` FOREIGN KEY (proposal_id) REFERENCES Company (id)');
        $this->addSql('ALTER TABLE VacancyUpdate ADD CONSTRAINT `FK_7F81E845108B7592` FOREIGN KEY (original_id) REFERENCES Vacancy (id)');
        $this->addSql('ALTER TABLE VacancyUpdate ADD CONSTRAINT `FK_7F81E845F4792058` FOREIGN KEY (proposal_id) REFERENCES Vacancy (id)');
        $this->addSql('ALTER TABLE Company DROP FOREIGN KEY FK_800230D32796CA52');
        $this->addSql('ALTER TABLE Company DROP FOREIGN KEY FK_800230D3A892657C');
        $this->addSql('DROP INDEX IDX_800230D32796CA52 ON Company');
        $this->addSql('DROP INDEX IDX_800230D3A892657C ON Company');
        $this->addSql('ALTER TABLE Company ADD slogan_id INT NOT NULL, ADD description_id INT NOT NULL, ADD website_id INT NOT NULL, ADD approver_id INT DEFAULT NULL, ADD contactName VARCHAR(255) DEFAULT NULL, ADD contactAddress VARCHAR(255) DEFAULT NULL, ADD contactEmail VARCHAR(255) DEFAULT NULL, ADD contactPhone VARCHAR(255) DEFAULT NULL, ADD logo VARCHAR(255) DEFAULT NULL, ADD approved INT NOT NULL, ADD approvedAt DATETIME DEFAULT NULL, ADD isUpdate TINYINT NOT NULL, ADD approvableText_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE Vacancy DROP FOREIGN KEY FK_668955212796CA52');
        $this->addSql('ALTER TABLE Vacancy DROP FOREIGN KEY FK_66895521A892657C');
        $this->addSql('DROP INDEX IDX_668955212796CA52 ON Vacancy');
        $this->addSql('DROP INDEX IDX_66895521A892657C ON Vacancy');
        $this->addSql('ALTER TABLE Vacancy ADD name_id INT NOT NULL, ADD location_id INT NOT NULL, ADD website_id INT NOT NULL, ADD description_id INT NOT NULL, ADD attachment_id INT NOT NULL, ADD category_id INT NOT NULL, ADD approver_id INT DEFAULT NULL, ADD contactName VARCHAR(255) DEFAULT NULL, ADD contactPhone VARCHAR(255) DEFAULT NULL, ADD contactEmail VARCHAR(255) DEFAULT NULL, ADD approved INT NOT NULL, ADD approvedAt DATETIME DEFAULT NULL, ADD isUpdate TINYINT NOT NULL, ADD approvableText_id INT DEFAULT NULL');
        $this->addSql(<<<'SQL'
            UPDATE Company c
            JOIN CompanyRevision r ON r.id = COALESCE(c.liveRevision_id, c.currentRevision_id)
            SET c.slogan_id = r.slogan_id, c.description_id = r.description_id, c.website_id = r.website_id,
                c.approver_id = r.reviewer_id, c.contactName = r.contactName, c.contactAddress = r.contactAddress,
                c.contactEmail = r.contactEmail, c.contactPhone = r.contactPhone, c.logo = r.logo,
                c.approvedAt = r.reviewedAt, c.isUpdate = 0,
                c.approved = CASE r.status WHEN 'approved' THEN 1 WHEN 'rejected' THEN 2 ELSE 0 END
            SQL);
        $this->addSql(<<<'SQL'
            UPDATE Vacancy v
            JOIN VacancyRevision r ON r.id = COALESCE(v.liveRevision_id, v.currentRevision_id)
            SET v.name_id = r.name_id, v.location_id = r.location_id, v.website_id = r.website_id,
                v.description_id = r.description_id, v.attachment_id = r.attachment_id, v.category_id = r.category_id,
                v.approver_id = r.reviewer_id, v.contactName = r.contactName, v.contactPhone = r.contactPhone,
                v.contactEmail = r.contactEmail, v.approvedAt = r.reviewedAt, v.isUpdate = 0,
                v.approved = CASE r.status WHEN 'approved' THEN 1 WHEN 'rejected' THEN 2 ELSE 0 END
            SQL);
        $this->addSql('ALTER TABLE CompanyRevision DROP FOREIGN KEY FK_48CAB2AEF675F31B');
        $this->addSql('ALTER TABLE CompanyRevision DROP FOREIGN KEY FK_48CAB2AEFD16CEE4');
        $this->addSql('ALTER TABLE CompanyRevision DROP FOREIGN KEY FK_48CAB2AE70574616');
        $this->addSql('ALTER TABLE CompanyRevision DROP FOREIGN KEY FK_48CAB2AE979B1AD6');
        $this->addSql('ALTER TABLE CompanyRevision DROP FOREIGN KEY FK_48CAB2AE8F2D4199');
        $this->addSql('ALTER TABLE CompanyRevision DROP FOREIGN KEY FK_48CAB2AE26C79F4B');
        $this->addSql('ALTER TABLE CompanyRevision DROP FOREIGN KEY FK_48CAB2AED9F966B');
        $this->addSql('ALTER TABLE CompanyRevision DROP FOREIGN KEY FK_48CAB2AE18F45C82');
        $this->addSql('ALTER TABLE CompanyRevisionComment DROP FOREIGN KEY FK_E65AF115F675F31B');
        $this->addSql('ALTER TABLE CompanyRevisionComment DROP FOREIGN KEY FK_E65AF1151DFA7C8F');
        $this->addSql('ALTER TABLE VacancyRevision DROP FOREIGN KEY FK_FFE914BFF675F31B');
        $this->addSql('ALTER TABLE VacancyRevision DROP FOREIGN KEY FK_FFE914BFFD16CEE4');
        $this->addSql('ALTER TABLE VacancyRevision DROP FOREIGN KEY FK_FFE914BF70574616');
        $this->addSql('ALTER TABLE VacancyRevision DROP FOREIGN KEY FK_FFE914BF433B78C4');
        $this->addSql('ALTER TABLE VacancyRevision DROP FOREIGN KEY FK_FFE914BF8F2D4199');
        $this->addSql('ALTER TABLE VacancyRevision DROP FOREIGN KEY FK_FFE914BF71179CD6');
        $this->addSql('ALTER TABLE VacancyRevision DROP FOREIGN KEY FK_FFE914BF64D218E');
        $this->addSql('ALTER TABLE VacancyRevision DROP FOREIGN KEY FK_FFE914BF18F45C82');
        $this->addSql('ALTER TABLE VacancyRevision DROP FOREIGN KEY FK_FFE914BFD9F966B');
        $this->addSql('ALTER TABLE VacancyRevision DROP FOREIGN KEY FK_FFE914BF464E68B');
        $this->addSql('ALTER TABLE VacancyRevision DROP FOREIGN KEY FK_FFE914BF12469DE2');
        $this->addSql('ALTER TABLE VacancyRevisionComment DROP FOREIGN KEY FK_EE72B76BF675F31B');
        $this->addSql('ALTER TABLE VacancyRevisionComment DROP FOREIGN KEY FK_EE72B76B1DFA7C8F');
        $this->addSql('ALTER TABLE Company DROP currentRevision_id, DROP liveRevision_id');
        $this->addSql('ALTER TABLE Vacancy DROP currentRevision_id, DROP liveRevision_id');
        $this->addSql('DROP TABLE CompanyRevision');
        $this->addSql('DROP TABLE CompanyRevisionComment');
        $this->addSql('DROP TABLE VacancyRevision');
        $this->addSql('DROP TABLE VacancyRevisionComment');
        $this->addSql('ALTER TABLE Company ADD CONSTRAINT `FK_800230D318F45C82` FOREIGN KEY (website_id) REFERENCES CareerLocalisedText (id)');
        $this->addSql('ALTER TABLE Company ADD CONSTRAINT `FK_800230D326C79F4B` FOREIGN KEY (slogan_id) REFERENCES CareerLocalisedText (id)');
        $this->addSql('ALTER TABLE Company ADD CONSTRAINT `FK_800230D3502BCAA2` FOREIGN KEY (approvableText_id) REFERENCES ApprovableText (id)');
        $this->addSql('ALTER TABLE Company ADD CONSTRAINT `FK_800230D3BB23766C` FOREIGN KEY (approver_id) REFERENCES Member (lidnr)');
        $this->addSql('ALTER TABLE Company ADD CONSTRAINT `FK_800230D3D9F966B` FOREIGN KEY (description_id) REFERENCES CareerLocalisedText (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_800230D3502BCAA2 ON Company (approvableText_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_800230D326C79F4B ON Company (slogan_id)');
        $this->addSql('CREATE INDEX IDX_800230D3BB23766C ON Company (approver_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_800230D3D9F966B ON Company (description_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_800230D318F45C82 ON Company (website_id)');
        $this->addSql('ALTER TABLE Vacancy ADD CONSTRAINT `FK_6689552112469DE2` FOREIGN KEY (category_id) REFERENCES VacancyCategory (id)');
        $this->addSql('ALTER TABLE Vacancy ADD CONSTRAINT `FK_C395A61818F45C82` FOREIGN KEY (website_id) REFERENCES CareerLocalisedText (id)');
        $this->addSql('ALTER TABLE Vacancy ADD CONSTRAINT `FK_C395A618464E68B` FOREIGN KEY (attachment_id) REFERENCES CareerLocalisedText (id)');
        $this->addSql('ALTER TABLE Vacancy ADD CONSTRAINT `FK_C395A618502BCAA2` FOREIGN KEY (approvableText_id) REFERENCES ApprovableText (id)');
        $this->addSql('ALTER TABLE Vacancy ADD CONSTRAINT `FK_C395A61864D218E` FOREIGN KEY (location_id) REFERENCES CareerLocalisedText (id)');
        $this->addSql('ALTER TABLE Vacancy ADD CONSTRAINT `FK_C395A61871179CD6` FOREIGN KEY (name_id) REFERENCES CareerLocalisedText (id)');
        $this->addSql('ALTER TABLE Vacancy ADD CONSTRAINT `FK_C395A618BB23766C` FOREIGN KEY (approver_id) REFERENCES Member (lidnr)');
        $this->addSql('ALTER TABLE Vacancy ADD CONSTRAINT `FK_C395A618D9F966B` FOREIGN KEY (description_id) REFERENCES CareerLocalisedText (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_6689552164D218E ON Vacancy (location_id)');
        $this->addSql('CREATE INDEX IDX_66895521BB23766C ON Vacancy (approver_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_66895521502BCAA2 ON Vacancy (approvableText_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_6689552118F45C82 ON Vacancy (website_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_66895521D9F966B ON Vacancy (description_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_6689552171179CD6 ON Vacancy (name_id)');
        $this->addSql('CREATE INDEX IDX_6689552112469DE2 ON Vacancy (category_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_66895521464E68B ON Vacancy (attachment_id)');

        // Career: rename Vacancy -> Job back
        $this->addSql('ALTER TABLE Vacancy DROP FOREIGN KEY FK_6689552112469DE2');
        $this->addSql('ALTER TABLE VacancyLabelAssignment DROP FOREIGN KEY FK_238B465E433B78C4');
        $this->addSql('ALTER TABLE VacancyLabelAssignment DROP FOREIGN KEY FK_238B465ED0807282');
        $this->addSql('ALTER TABLE VacancyUpdate DROP FOREIGN KEY FK_7F81E845108B7592');
        $this->addSql('ALTER TABLE VacancyUpdate DROP FOREIGN KEY FK_7F81E845F4792058');
        $this->addSql('ALTER TABLE Vacancy RENAME INDEX UNIQ_6689552171179CD6 TO UNIQ_C395A61871179CD6');
        $this->addSql('ALTER TABLE Vacancy RENAME INDEX UNIQ_6689552164D218E TO UNIQ_C395A61864D218E');
        $this->addSql('ALTER TABLE Vacancy RENAME INDEX UNIQ_6689552118F45C82 TO UNIQ_C395A61818F45C82');
        $this->addSql('ALTER TABLE Vacancy RENAME INDEX UNIQ_66895521D9F966B TO UNIQ_C395A618D9F966B');
        $this->addSql('ALTER TABLE Vacancy RENAME INDEX UNIQ_66895521464E68B TO UNIQ_C395A618464E68B');
        $this->addSql('ALTER TABLE Vacancy RENAME INDEX UNIQ_66895521502BCAA2 TO UNIQ_C395A618502BCAA2');
        $this->addSql('ALTER TABLE Vacancy RENAME INDEX IDX_66895521F44CABFF TO IDX_C395A618F44CABFF');
        $this->addSql('ALTER TABLE Vacancy RENAME INDEX IDX_6689552112469DE2 TO IDX_C395A61812469DE2');
        $this->addSql('ALTER TABLE Vacancy RENAME INDEX IDX_66895521BB23766C TO IDX_C395A618BB23766C');
        $this->addSql('ALTER TABLE VacancyCategory RENAME INDEX UNIQ_94C618B271179CD6 TO UNIQ_9701A2A671179CD6');
        $this->addSql('ALTER TABLE VacancyCategory RENAME INDEX UNIQ_94C618B2A8BEABC TO UNIQ_9701A2A6A8BEABC');
        $this->addSql('ALTER TABLE VacancyCategory RENAME INDEX UNIQ_94C618B2311966CE TO UNIQ_9701A2A6311966CE');
        $this->addSql('ALTER TABLE VacancyLabel RENAME INDEX UNIQ_12576AE671179CD6 TO UNIQ_ECF91BE071179CD6');
        $this->addSql('ALTER TABLE VacancyLabel RENAME INDEX UNIQ_12576AE6BF69284D TO UNIQ_ECF91BE0BF69284D');
        $this->addSql('ALTER TABLE VacancyUpdate RENAME INDEX UNIQ_7F81E845F4792058 TO UNIQ_961CE301F4792058');
        $this->addSql('ALTER TABLE VacancyUpdate RENAME INDEX IDX_7F81E845108B7592 TO IDX_961CE301108B7592');
        $this->addSql('ALTER TABLE VacancyLabelAssignment RENAME INDEX IDX_238B465E433B78C4 TO IDX_42CB55B5BE04EA9');
        $this->addSql('ALTER TABLE VacancyLabelAssignment RENAME INDEX IDX_238B465ED0807282 TO IDX_42CB55B5DD1E79DF');
        $this->addSql('ALTER TABLE VacancyLabelAssignment CHANGE vacancy_id job_id INT NOT NULL');
        $this->addSql('ALTER TABLE VacancyLabelAssignment CHANGE vacancylabel_id joblabel_id INT NOT NULL');
        $this->addSql('RENAME TABLE Vacancy TO Job');
        $this->addSql('RENAME TABLE VacancyCategory TO JobCategory');
        $this->addSql('RENAME TABLE VacancyLabel TO JobLabel');
        $this->addSql('RENAME TABLE VacancyLabelAssignment TO JobLabelAssignment');
        $this->addSql('RENAME TABLE VacancyUpdate TO JobUpdate');
        $this->addSql('ALTER TABLE Job ADD CONSTRAINT FK_C395A61812469DE2 FOREIGN KEY (category_id) REFERENCES JobCategory (id)');
        $this->addSql('ALTER TABLE JobLabelAssignment ADD CONSTRAINT FK_42CB55B5BE04EA9 FOREIGN KEY (job_id) REFERENCES Job (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE JobLabelAssignment ADD CONSTRAINT FK_42CB55B5DD1E79DF FOREIGN KEY (joblabel_id) REFERENCES JobLabel (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE JobUpdate ADD CONSTRAINT FK_961CE301108B7592 FOREIGN KEY (original_id) REFERENCES Job (id)');
        $this->addSql('ALTER TABLE JobUpdate ADD CONSTRAINT FK_961CE301F4792058 FOREIGN KEY (proposal_id) REFERENCES Job (id)');

        // SignupField.type (reverse)
        $this->addSql("UPDATE SignupField SET type = CASE type WHEN 'yes-no' THEN '1' WHEN 'number' THEN '2' WHEN 'choice' THEN '3' ELSE '0' END");
        $this->addSql('ALTER TABLE SignupField CHANGE type type INT NOT NULL');

        // Activities (reverse)
        $this->addSql('ALTER TABLE Activity ADD name_id INT DEFAULT NULL, ADD location_id INT DEFAULT NULL, ADD costs_id INT DEFAULT NULL, ADD approver_id INT DEFAULT NULL, ADD description_id INT DEFAULT NULL, ADD beginTime DATETIME DEFAULT NULL, ADD endTime DATETIME DEFAULT NULL, ADD status INT DEFAULT NULL, ADD requireGEFLITST TINYINT DEFAULT NULL, ADD requireZettle TINYINT DEFAULT NULL, ADD category VARCHAR(255) DEFAULT NULL');
        $this->addSql(<<<'SQL'
            UPDATE Activity a
            JOIN ActivityRevision r ON r.id = COALESCE(a.liveRevision_id, a.currentRevision_id)
            SET a.name_id = r.name_id, a.location_id = r.location_id, a.costs_id = r.costs_id, a.description_id = r.description_id,
                a.beginTime = r.beginTime, a.endTime = r.endTime, a.category = r.category,
                a.requireGEFLITST = r.requireGEFLITST, a.requireZettle = r.requireZettle, a.approver_id = r.reviewer_id,
                a.status = CASE r.status WHEN 'approved' THEN 2 WHEN 'rejected' THEN 3 ELSE 1 END
            SQL);
        // Restore the original NOT NULL-ness now that every surviving row has been backfilled (approver_id was always
        // nullable and stays so).
        $this->addSql('ALTER TABLE Activity MODIFY name_id INT NOT NULL, MODIFY location_id INT NOT NULL, MODIFY costs_id INT NOT NULL, MODIFY description_id INT NOT NULL, MODIFY beginTime DATETIME NOT NULL, MODIFY endTime DATETIME NOT NULL, MODIFY status INT NOT NULL, MODIFY requireGEFLITST TINYINT NOT NULL, MODIFY requireZettle TINYINT NOT NULL, MODIFY category VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE Activity DROP FOREIGN KEY FK_55026B0C2796CA52');
        $this->addSql('ALTER TABLE Activity DROP FOREIGN KEY FK_55026B0CA892657C');
        $this->addSql('DROP INDEX IDX_55026B0C2796CA52 ON Activity');
        $this->addSql('DROP INDEX IDX_55026B0CA892657C ON Activity');
        $this->addSql('ALTER TABLE ActivityRevision DROP FOREIGN KEY FK_F7309B7AF675F31B');
        $this->addSql('ALTER TABLE ActivityRevision DROP FOREIGN KEY FK_F7309B7AFD16CEE4');
        $this->addSql('ALTER TABLE ActivityRevision DROP FOREIGN KEY FK_F7309B7A70574616');
        $this->addSql('ALTER TABLE ActivityRevision DROP FOREIGN KEY FK_F7309B7A81C06096');
        $this->addSql('ALTER TABLE ActivityRevision DROP FOREIGN KEY FK_F7309B7A8F2D4199');
        $this->addSql('ALTER TABLE ActivityRevision DROP FOREIGN KEY FK_F7309B7A71179CD6');
        $this->addSql('ALTER TABLE ActivityRevision DROP FOREIGN KEY FK_F7309B7A64D218E');
        $this->addSql('ALTER TABLE ActivityRevision DROP FOREIGN KEY FK_F7309B7A27D66E0D');
        $this->addSql('ALTER TABLE ActivityRevision DROP FOREIGN KEY FK_F7309B7AD9F966B');
        $this->addSql('ALTER TABLE ActivityRevisionComment DROP FOREIGN KEY FK_DEE0948DF675F31B');
        $this->addSql('ALTER TABLE ActivityRevisionComment DROP FOREIGN KEY FK_DEE0948D1DFA7C8F');
        $this->addSql('ALTER TABLE Activity DROP currentRevision_id, DROP liveRevision_id');
        $this->addSql('DROP TABLE ActivityRevision');
        $this->addSql('DROP TABLE ActivityRevisionComment');
        $this->addSql('ALTER TABLE Activity ADD CONSTRAINT `FK_55026B0C27D66E0D` FOREIGN KEY (costs_id) REFERENCES ActivityLocalisedText (id)');
        $this->addSql('ALTER TABLE Activity ADD CONSTRAINT `FK_55026B0C64D218E` FOREIGN KEY (location_id) REFERENCES ActivityLocalisedText (id)');
        $this->addSql('ALTER TABLE Activity ADD CONSTRAINT `FK_55026B0C71179CD6` FOREIGN KEY (name_id) REFERENCES ActivityLocalisedText (id)');
        $this->addSql('ALTER TABLE Activity ADD CONSTRAINT `FK_55026B0CBB23766C` FOREIGN KEY (approver_id) REFERENCES Member (lidnr)');
        $this->addSql('ALTER TABLE Activity ADD CONSTRAINT `FK_55026B0CD9F966B` FOREIGN KEY (description_id) REFERENCES ActivityLocalisedText (id)');
        $this->addSql('CREATE INDEX IDX_55026B0CBB23766C ON Activity (approver_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_55026B0C64D218E ON Activity (location_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_55026B0C27D66E0D ON Activity (costs_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_55026B0CD9F966B ON Activity (description_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_55026B0C71179CD6 ON Activity (name_id)');
    }
}
