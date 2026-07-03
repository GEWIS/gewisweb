<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Revision/approval workflow for activities and the careers portal (consolidated feature migration).
 *
 * This is the squash of the ten incremental migrations authored while building the revision-workflow feature. The
 * original statements are reproduced verbatim (every up() in chronological order, every down() in reverse) so the
 * production data carry-forward is preserved exactly; this is NOT a fresh schema diff. The folded steps are:
 *   1. Split Activity/Company/Vacancy into stable aggregates + immutable revision chains; rename Job -> Vacancy;
 *      migrate SignupField.type to a string-backed enum; carry legacy rows into revision 1; drop the Approvable* model.
 *   2. Move sign-up lists from the activity onto the activity revision (stable per-list lineageId).
 *   3. Move the organising organ/company and labels onto the activity revision, and labels onto the vacancy revision.
 *   4. Re-point revision-comment authors from Member to User (and allow company-user authors).
 *   5. Add the EditLock table plus optimistic-lock version + last-editor columns on the revision tables.
 *   6. Add the ActivityRevisionEdit audit trail.
 *   7. Add the numeric capacity of a (limited-capacity) sign-up list.
 *   8. Add the draw lock/audit (drawnAt/drawnBy) to a sign-up list.
 *   9. Add allocationMethod and its per-method settings to a sign-up list.
 *  10. Add external sign-up email-verification tokens and a policy-agreement and email-confirmation timestamp on
 *      sign-ups.
 *
 * phpcs:disable Generic.Files.LineLength.TooLong
 * phpcs:disable SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall
 */
final class Version20260529150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Revision/approval workflow for activities + careers, sign-up versioning, edit locking, and external sign-up verification (squashed feature migration). GH-2067.';
    }

    public function up(Schema $schema): void
    {
        // ── Version20260529150000: Revision/approval workflow for activities + careers (split aggregates, Job->Vacancy rename, dual-actor authorship). GH-2067.
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

        // ── Version20260530112824: Move sign-up lists from the activity onto the activity revision.
        // Add the new revision FK and the lineage id as nullable, backfill from the existing rows (a list moves to its
        // activity's live revision, else its current revision; the lineage id is a fresh UUID stored as 16 raw bytes),
        // then finalise to NOT NULL and swap the foreign key. Every activity has at least a current revision.
        $this->addSql('ALTER TABLE SignupList ADD activity_revision_id INT DEFAULT NULL, ADD lineageId BINARY(16) DEFAULT NULL');
        $this->addSql(<<<'SQL'
            UPDATE SignupList sl
            JOIN Activity a ON sl.activity_id = a.id
            SET sl.activity_revision_id = COALESCE(a.liveRevision_id, a.currentRevision_id),
                sl.lineageId = UNHEX(REPLACE(UUID(), '-', ''))
            SQL);
        $this->addSql('ALTER TABLE SignupList DROP FOREIGN KEY `FK_274D085F81C06096`');
        $this->addSql('DROP INDEX IDX_274D085F81C06096 ON SignupList');
        $this->addSql('ALTER TABLE SignupList DROP activity_id');
        $this->addSql('ALTER TABLE SignupList CHANGE activity_revision_id activity_revision_id INT NOT NULL');
        $this->addSql('ALTER TABLE SignupList CHANGE lineageId lineageId BINARY(16) NOT NULL');
        $this->addSql('ALTER TABLE SignupList ADD CONSTRAINT FK_274D085F13741683 FOREIGN KEY (activity_revision_id) REFERENCES ActivityRevision (id)');
        $this->addSql('CREATE INDEX IDX_274D085F13741683 ON SignupList (activity_revision_id)');
        // A revision never holds two lists of the same lineage.
        $this->addSql('CREATE UNIQUE INDEX signup_list_revision_lineage_uniq ON SignupList (activity_revision_id, lineageId)');

        // ── Version20260530192012: Move organ/company/labels onto the activity revision and labels onto the vacancy revision.
        // Add label assignment revision tables and add organ and company to a revision.
        $this->addSql('CREATE TABLE ActivityRevisionLabelAssignment (activityrevision_id INT NOT NULL, activitylabel_id INT NOT NULL, INDEX IDX_AD4B45A22B53B2FF (activityrevision_id), INDEX IDX_AD4B45A247A3B8A4 (activitylabel_id), PRIMARY KEY (activityrevision_id, activitylabel_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE ActivityRevisionLabelAssignment ADD CONSTRAINT FK_AD4B45A22B53B2FF FOREIGN KEY (activityrevision_id) REFERENCES ActivityRevision (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE ActivityRevisionLabelAssignment ADD CONSTRAINT FK_AD4B45A247A3B8A4 FOREIGN KEY (activitylabel_id) REFERENCES ActivityLabel (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE ActivityRevision ADD organ_id INT DEFAULT NULL, ADD company_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE ActivityRevision ADD CONSTRAINT FK_F7309B7AE4445171 FOREIGN KEY (organ_id) REFERENCES Organ (id)');
        $this->addSql('ALTER TABLE ActivityRevision ADD CONSTRAINT FK_F7309B7A979B1AD6 FOREIGN KEY (company_id) REFERENCES Company (id)');
        $this->addSql('CREATE INDEX IDX_F7309B7AE4445171 ON ActivityRevision (organ_id)');
        $this->addSql('CREATE INDEX IDX_F7309B7A979B1AD6 ON ActivityRevision (company_id)');

        // Backfill every revision with its activity's organ/company and label assignments (labels were never versioned,
        // so copying onto every revision keeps a revision and its predecessor in sync).
        $this->addSql(<<<'SQL'
            UPDATE ActivityRevision r
            JOIN Activity a ON r.activity_id = a.id
            SET r.organ_id = a.organ_id,
                r.company_id = a.company_id
        SQL);
        $this->addSql(<<<'SQL'
            INSERT INTO ActivityRevisionLabelAssignment (activityrevision_id, activitylabel_id)
            SELECT r.id, la.activitylabel_id
            FROM ActivityRevision r
            JOIN ActivityLabelAssignment la ON la.activity_id = r.activity_id
        SQL);

        // Drop the original labels on the activity.
        $this->addSql('ALTER TABLE ActivityLabelAssignment DROP FOREIGN KEY `FK_131965B847A3B8A4`');
        $this->addSql('ALTER TABLE ActivityLabelAssignment DROP FOREIGN KEY `FK_131965B881C06096`');
        $this->addSql('DROP TABLE ActivityLabelAssignment');
        $this->addSql('ALTER TABLE Activity DROP FOREIGN KEY `FK_55026B0C979B1AD6`');
        $this->addSql('ALTER TABLE Activity DROP FOREIGN KEY `FK_55026B0CE4445171`');
        $this->addSql('DROP INDEX IDX_55026B0CE4445171 ON Activity');
        $this->addSql('DROP INDEX IDX_55026B0C979B1AD6 ON Activity');
        $this->addSql('ALTER TABLE Activity DROP organ_id, DROP company_id');

        // Vacancy labels: the same move but onto VacancyRevision.
        $this->addSql('CREATE TABLE VacancyRevisionLabelAssignment (vacancyrevision_id INT NOT NULL, vacancylabel_id INT NOT NULL, INDEX IDX_E72E458B84E1C68C (vacancyrevision_id), INDEX IDX_E72E458BD0807282 (vacancylabel_id), PRIMARY KEY (vacancyrevision_id, vacancylabel_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE VacancyRevisionLabelAssignment ADD CONSTRAINT FK_E72E458B84E1C68C FOREIGN KEY (vacancyrevision_id) REFERENCES VacancyRevision (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE VacancyRevisionLabelAssignment ADD CONSTRAINT FK_E72E458BD0807282 FOREIGN KEY (vacancylabel_id) REFERENCES VacancyLabel (id) ON DELETE CASCADE');
        $this->addSql(<<<'SQL'
            INSERT INTO VacancyRevisionLabelAssignment (vacancyrevision_id, vacancylabel_id)
            SELECT r.id, la.vacancylabel_id
            FROM VacancyRevision r
            JOIN VacancyLabelAssignment la ON la.vacancy_id = r.vacancy_id
        SQL);
        $this->addSql('ALTER TABLE VacancyLabelAssignment DROP FOREIGN KEY `FK_238B465E433B78C4`');
        $this->addSql('ALTER TABLE VacancyLabelAssignment DROP FOREIGN KEY `FK_238B465ED0807282`');
        $this->addSql('DROP TABLE VacancyLabelAssignment');

        // ── Version20260530194212: Make revision comment authors users (or company users) instead of members.
        // Re-point author_id (Member -> User), make it nullable, and add the company-user author column to each
        // concrete revision-comment table (the fields live on the AbstractRevisionComment mapped superclass).
        $this->addSql('ALTER TABLE ActivityRevisionComment DROP FOREIGN KEY `FK_DEE0948DF675F31B`');
        $this->addSql('ALTER TABLE ActivityRevisionComment ADD authorCompanyUser_id INT DEFAULT NULL, CHANGE author_id author_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE ActivityRevisionComment ADD CONSTRAINT FK_DEE0948DF675F31B FOREIGN KEY (author_id) REFERENCES User (lidnr)');
        $this->addSql('ALTER TABLE ActivityRevisionComment ADD CONSTRAINT FK_DEE0948DFD16CEE4 FOREIGN KEY (authorCompanyUser_id) REFERENCES CompanyUser (id)');
        $this->addSql('CREATE INDEX IDX_DEE0948DFD16CEE4 ON ActivityRevisionComment (authorCompanyUser_id)');
        $this->addSql('ALTER TABLE CompanyRevisionComment DROP FOREIGN KEY `FK_E65AF115F675F31B`');
        $this->addSql('ALTER TABLE CompanyRevisionComment ADD authorCompanyUser_id INT DEFAULT NULL, CHANGE author_id author_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE CompanyRevisionComment ADD CONSTRAINT FK_E65AF115F675F31B FOREIGN KEY (author_id) REFERENCES User (lidnr)');
        $this->addSql('ALTER TABLE CompanyRevisionComment ADD CONSTRAINT FK_E65AF115FD16CEE4 FOREIGN KEY (authorCompanyUser_id) REFERENCES CompanyUser (id)');
        $this->addSql('CREATE INDEX IDX_E65AF115FD16CEE4 ON CompanyRevisionComment (authorCompanyUser_id)');
        $this->addSql('ALTER TABLE VacancyRevisionComment DROP FOREIGN KEY `FK_EE72B76BF675F31B`');
        $this->addSql('ALTER TABLE VacancyRevisionComment ADD authorCompanyUser_id INT DEFAULT NULL, CHANGE author_id author_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE VacancyRevisionComment ADD CONSTRAINT FK_EE72B76BF675F31B FOREIGN KEY (author_id) REFERENCES User (lidnr)');
        $this->addSql('ALTER TABLE VacancyRevisionComment ADD CONSTRAINT FK_EE72B76BFD16CEE4 FOREIGN KEY (authorCompanyUser_id) REFERENCES CompanyUser (id)');
        $this->addSql('CREATE INDEX IDX_EE72B76BFD16CEE4 ON VacancyRevisionComment (authorCompanyUser_id)');

        // ── Version20260531184301: Add the EditLock table and version + last-editor columns to the revision tables.
        $this->addSql('CREATE TABLE EditLock (resourceId VARCHAR(32) NOT NULL, resourceKey INT NOT NULL, acquiredAt DATETIME NOT NULL, lastPingAt DATETIME NOT NULL, id INT AUTO_INCREMENT NOT NULL, lockedBy_id INT DEFAULT NULL, lockedByCompanyUser_id INT DEFAULT NULL, INDEX IDX_5EF688A71E253D71 (lockedBy_id), INDEX IDX_5EF688A7B7C41E8 (lockedByCompanyUser_id), UNIQUE INDEX edit_lock_resource_uniq (resourceId, resourceKey), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE EditLock ADD CONSTRAINT FK_5EF688A71E253D71 FOREIGN KEY (lockedBy_id) REFERENCES User (lidnr)');
        $this->addSql('ALTER TABLE EditLock ADD CONSTRAINT FK_5EF688A7B7C41E8 FOREIGN KEY (lockedByCompanyUser_id) REFERENCES CompanyUser (id)');
        $this->addSql('ALTER TABLE ActivityRevision ADD version INT DEFAULT 1 NOT NULL, ADD lastEditedBy_id INT DEFAULT NULL, ADD lastEditedByCompanyUser_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE ActivityRevision ADD CONSTRAINT FK_F7309B7AA19E445F FOREIGN KEY (lastEditedBy_id) REFERENCES User (lidnr)');
        $this->addSql('ALTER TABLE ActivityRevision ADD CONSTRAINT FK_F7309B7A102DD120 FOREIGN KEY (lastEditedByCompanyUser_id) REFERENCES CompanyUser (id)');
        $this->addSql('CREATE INDEX IDX_F7309B7AA19E445F ON ActivityRevision (lastEditedBy_id)');
        $this->addSql('CREATE INDEX IDX_F7309B7A102DD120 ON ActivityRevision (lastEditedByCompanyUser_id)');
        $this->addSql('ALTER TABLE CompanyRevision ADD version INT DEFAULT 1 NOT NULL, ADD lastEditedBy_id INT DEFAULT NULL, ADD lastEditedByCompanyUser_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE CompanyRevision ADD CONSTRAINT FK_48CAB2AEA19E445F FOREIGN KEY (lastEditedBy_id) REFERENCES User (lidnr)');
        $this->addSql('ALTER TABLE CompanyRevision ADD CONSTRAINT FK_48CAB2AE102DD120 FOREIGN KEY (lastEditedByCompanyUser_id) REFERENCES CompanyUser (id)');
        $this->addSql('CREATE INDEX IDX_48CAB2AEA19E445F ON CompanyRevision (lastEditedBy_id)');
        $this->addSql('CREATE INDEX IDX_48CAB2AE102DD120 ON CompanyRevision (lastEditedByCompanyUser_id)');
        $this->addSql('ALTER TABLE VacancyRevision ADD version INT DEFAULT 1 NOT NULL, ADD lastEditedBy_id INT DEFAULT NULL, ADD lastEditedByCompanyUser_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE VacancyRevision ADD CONSTRAINT FK_FFE914BFA19E445F FOREIGN KEY (lastEditedBy_id) REFERENCES User (lidnr)');
        $this->addSql('ALTER TABLE VacancyRevision ADD CONSTRAINT FK_FFE914BF102DD120 FOREIGN KEY (lastEditedByCompanyUser_id) REFERENCES CompanyUser (id)');
        $this->addSql('CREATE INDEX IDX_FFE914BFA19E445F ON VacancyRevision (lastEditedBy_id)');
        $this->addSql('CREATE INDEX IDX_FFE914BF102DD120 ON VacancyRevision (lastEditedByCompanyUser_id)');

        // ── Version20260602175007: Add the activity revision edit audit trail.
        $this->addSql('CREATE TABLE ActivityRevisionEdit (editedAt DATETIME NOT NULL, changedFields JSON NOT NULL, id INT AUTO_INCREMENT NOT NULL, revision_id INT NOT NULL, editor_id INT NOT NULL, INDEX IDX_285C37811DFA7C8F (revision_id), INDEX IDX_285C37816995AC4C (editor_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE ActivityRevisionEdit ADD CONSTRAINT FK_285C37811DFA7C8F FOREIGN KEY (revision_id) REFERENCES ActivityRevision (id)');
        $this->addSql('ALTER TABLE ActivityRevisionEdit ADD CONSTRAINT FK_285C37816995AC4C FOREIGN KEY (editor_id) REFERENCES User (lidnr)');

        // ── Version20260606170538: Add the numeric capacity of a (limited-capacity) sign-up list.
        // The maximum number of admitted sign-ups for a limited-capacity list; null when unlimited.
        $this->addSql('ALTER TABLE SignupList ADD capacity INT DEFAULT NULL');

        // ── Version20260606184222: Record when a sign-up list draw was performed and by which board member (the draw lock + audit).
        // drawnAt (non-null = the draw is locked) and drawnBy (the board member who performed it).
        $this->addSql('ALTER TABLE SignupList ADD drawnAt DATETIME DEFAULT NULL, ADD drawnBy_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE SignupList ADD CONSTRAINT FK_274D085F4FA7FF98 FOREIGN KEY (drawnBy_id) REFERENCES Member (lidnr)');
        $this->addSql('CREATE INDEX IDX_274D085F4FA7FF98 ON SignupList (drawnBy_id)');

        // ── Version20260606184826: Add allocationMethod and its per-method settings to SignupList.
        // Add the columns nullable, backfill existing rows with the defaults, then enforce NOT NULL where required, so
        // the change is safe on a table that already holds sign-up lists.
        $this->addSql('ALTER TABLE SignupList ADD allocationMethod VARCHAR(255) DEFAULT NULL, ADD drawCutoffRule VARCHAR(255) DEFAULT NULL, ADD drawCutoffAt DATETIME DEFAULT NULL, ADD drawAfterDurationHours INT DEFAULT NULL, ADD externalPolicyUrl VARCHAR(255) DEFAULT NULL, ADD externalForceOrdering TINYINT DEFAULT NULL, ADD externalPaymentByExternal TINYINT DEFAULT NULL, ADD customMethodDescription LONGTEXT DEFAULT NULL');
        $this->addSql('UPDATE SignupList SET allocationMethod = \'first-come-first-served\', externalForceOrdering = 0, externalPaymentByExternal = 0');
        $this->addSql('ALTER TABLE SignupList CHANGE allocationMethod allocationMethod VARCHAR(255) NOT NULL, CHANGE externalForceOrdering externalForceOrdering TINYINT NOT NULL, CHANGE externalPaymentByExternal externalPaymentByExternal TINYINT NOT NULL');

        // ── Version20260607180918: Add external sign-up email verification tokens and a manually-added flag on external sign-ups.
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE ExternalSignupVerification (purpose VARCHAR(255) NOT NULL, selector VARCHAR(255) NOT NULL, hashedToken VARCHAR(255) NOT NULL, expiresAt DATETIME NOT NULL, id INT AUTO_INCREMENT NOT NULL, external_signup_id INT NOT NULL, INDEX IDX_D55257277B3A307A (external_signup_id), INDEX IDX_external_signup_verification_selector (selector), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE ExternalSignupVerification ADD CONSTRAINT FK_D55257277B3A307A FOREIGN KEY (external_signup_id) REFERENCES Signup (id) ON DELETE CASCADE');
        // The old schema did not record whether an external signed up themselves or was entered by an organiser, so
        // flag every pre-existing external as manual: the conservative reading, as it asserts no policy agreement
        // that was never recorded.
        $this->addSql('ALTER TABLE Signup ADD addedManually TINYINT DEFAULT NULL');
        $this->addSql("UPDATE Signup SET addedManually = 1 WHERE type = 'external'");
        // Every pre-existing external predates the double-opt-in machinery (the token table created above is still
        // empty at this point), so each one is already a confirmed subscriber; its confirmation is dated to its
        // creation.
        $this->addSql('ALTER TABLE Signup ADD verifiedAt DATETIME DEFAULT NULL');
        $this->addSql("UPDATE Signup SET verifiedAt = createdAt WHERE type = 'external'");
    }

    public function down(Schema $schema): void
    {
        // ── Version20260607180918: Add external sign-up email verification tokens and a manually-added flag on external sign-ups. (reverse)
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ExternalSignupVerification DROP FOREIGN KEY FK_D55257277B3A307A');
        $this->addSql('DROP TABLE ExternalSignupVerification');
        $this->addSql('ALTER TABLE Signup DROP addedManually, DROP verifiedAt');

        // ── Version20260606184826: Add allocationMethod and its per-method settings to SignupList. (reverse)
        $this->addSql('ALTER TABLE SignupList DROP allocationMethod, DROP drawCutoffRule, DROP drawCutoffAt, DROP drawAfterDurationHours, DROP externalPolicyUrl, DROP externalForceOrdering, DROP externalPaymentByExternal, DROP customMethodDescription');

        // ── Version20260606184222: Record when a sign-up list draw was performed and by which board member (the draw lock + audit). (reverse)
        $this->addSql('ALTER TABLE SignupList DROP FOREIGN KEY FK_274D085F4FA7FF98');
        $this->addSql('DROP INDEX IDX_274D085F4FA7FF98 ON SignupList');
        $this->addSql('ALTER TABLE SignupList DROP drawnAt, DROP drawnBy_id');

        // ── Version20260606170538: Add the numeric capacity of a (limited-capacity) sign-up list. (reverse)
        $this->addSql('ALTER TABLE SignupList DROP capacity');

        // ── Version20260602175007: Add the activity revision edit audit trail. (reverse)
        $this->addSql('ALTER TABLE ActivityRevisionEdit DROP FOREIGN KEY FK_285C37811DFA7C8F');
        $this->addSql('ALTER TABLE ActivityRevisionEdit DROP FOREIGN KEY FK_285C37816995AC4C');
        $this->addSql('DROP TABLE ActivityRevisionEdit');

        // ── Version20260531184301: Add the EditLock table and version + last-editor columns to the revision tables. (reverse)
        $this->addSql('ALTER TABLE EditLock DROP FOREIGN KEY FK_5EF688A71E253D71');
        $this->addSql('ALTER TABLE EditLock DROP FOREIGN KEY FK_5EF688A7B7C41E8');
        $this->addSql('DROP TABLE EditLock');
        $this->addSql('ALTER TABLE ActivityRevision DROP FOREIGN KEY FK_F7309B7AA19E445F');
        $this->addSql('ALTER TABLE ActivityRevision DROP FOREIGN KEY FK_F7309B7A102DD120');
        $this->addSql('DROP INDEX IDX_F7309B7AA19E445F ON ActivityRevision');
        $this->addSql('DROP INDEX IDX_F7309B7A102DD120 ON ActivityRevision');
        $this->addSql('ALTER TABLE ActivityRevision DROP version, DROP lastEditedBy_id, DROP lastEditedByCompanyUser_id');
        $this->addSql('ALTER TABLE CompanyRevision DROP FOREIGN KEY FK_48CAB2AEA19E445F');
        $this->addSql('ALTER TABLE CompanyRevision DROP FOREIGN KEY FK_48CAB2AE102DD120');
        $this->addSql('DROP INDEX IDX_48CAB2AEA19E445F ON CompanyRevision');
        $this->addSql('DROP INDEX IDX_48CAB2AE102DD120 ON CompanyRevision');
        $this->addSql('ALTER TABLE CompanyRevision DROP version, DROP lastEditedBy_id, DROP lastEditedByCompanyUser_id');
        $this->addSql('ALTER TABLE VacancyRevision DROP FOREIGN KEY FK_FFE914BFA19E445F');
        $this->addSql('ALTER TABLE VacancyRevision DROP FOREIGN KEY FK_FFE914BF102DD120');
        $this->addSql('DROP INDEX IDX_FFE914BFA19E445F ON VacancyRevision');
        $this->addSql('DROP INDEX IDX_FFE914BF102DD120 ON VacancyRevision');
        $this->addSql('ALTER TABLE VacancyRevision DROP version, DROP lastEditedBy_id, DROP lastEditedByCompanyUser_id');

        // ── Version20260530194212: Make revision comment authors users (or company users) instead of members. (reverse)
        // Restore the company-user-less, member-anchored author on each comment table.
        $this->addSql('ALTER TABLE ActivityRevisionComment DROP FOREIGN KEY FK_DEE0948DF675F31B');
        $this->addSql('ALTER TABLE ActivityRevisionComment DROP FOREIGN KEY FK_DEE0948DFD16CEE4');
        $this->addSql('DROP INDEX IDX_DEE0948DFD16CEE4 ON ActivityRevisionComment');
        $this->addSql('ALTER TABLE ActivityRevisionComment DROP authorCompanyUser_id, CHANGE author_id author_id INT NOT NULL');
        $this->addSql('ALTER TABLE ActivityRevisionComment ADD CONSTRAINT `FK_DEE0948DF675F31B` FOREIGN KEY (author_id) REFERENCES Member (lidnr)');
        $this->addSql('ALTER TABLE CompanyRevisionComment DROP FOREIGN KEY FK_E65AF115F675F31B');
        $this->addSql('ALTER TABLE CompanyRevisionComment DROP FOREIGN KEY FK_E65AF115FD16CEE4');
        $this->addSql('DROP INDEX IDX_E65AF115FD16CEE4 ON CompanyRevisionComment');
        $this->addSql('ALTER TABLE CompanyRevisionComment DROP authorCompanyUser_id, CHANGE author_id author_id INT NOT NULL');
        $this->addSql('ALTER TABLE CompanyRevisionComment ADD CONSTRAINT `FK_E65AF115F675F31B` FOREIGN KEY (author_id) REFERENCES Member (lidnr)');
        $this->addSql('ALTER TABLE VacancyRevisionComment DROP FOREIGN KEY FK_EE72B76BF675F31B');
        $this->addSql('ALTER TABLE VacancyRevisionComment DROP FOREIGN KEY FK_EE72B76BFD16CEE4');
        $this->addSql('DROP INDEX IDX_EE72B76BFD16CEE4 ON VacancyRevisionComment');
        $this->addSql('ALTER TABLE VacancyRevisionComment DROP authorCompanyUser_id, CHANGE author_id author_id INT NOT NULL');
        $this->addSql('ALTER TABLE VacancyRevisionComment ADD CONSTRAINT `FK_EE72B76BF675F31B` FOREIGN KEY (author_id) REFERENCES Member (lidnr)');

        // ── Version20260530192012: Move organ/company/labels onto the activity revision and labels onto the vacancy revision. (reverse)
        // Vacancy labels: restore the table, backfill from the display revision, drop the new table.
        $this->addSql('CREATE TABLE VacancyLabelAssignment (vacancy_id INT NOT NULL, vacancylabel_id INT NOT NULL, INDEX IDX_238B465E433B78C4 (vacancy_id), INDEX IDX_238B465ED0807282 (vacancylabel_id), PRIMARY KEY (vacancy_id, vacancylabel_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_uca1400_ai_ci` ENGINE = InnoDB COMMENT = \'\'');
        $this->addSql('ALTER TABLE VacancyLabelAssignment ADD CONSTRAINT `FK_238B465E433B78C4` FOREIGN KEY (vacancy_id) REFERENCES Vacancy (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE VacancyLabelAssignment ADD CONSTRAINT `FK_238B465ED0807282` FOREIGN KEY (vacancylabel_id) REFERENCES VacancyLabel (id) ON DELETE CASCADE');
        $this->addSql(<<<'SQL'
            INSERT INTO VacancyLabelAssignment (vacancy_id, vacancylabel_id)
            SELECT v.id, ra.vacancylabel_id
            FROM Vacancy v
            JOIN VacancyRevisionLabelAssignment ra
                ON ra.vacancyrevision_id = COALESCE(v.liveRevision_id, v.currentRevision_id)
        SQL);
        $this->addSql('ALTER TABLE VacancyRevisionLabelAssignment DROP FOREIGN KEY FK_E72E458B84E1C68C');
        $this->addSql('ALTER TABLE VacancyRevisionLabelAssignment DROP FOREIGN KEY FK_E72E458BD0807282');
        $this->addSql('DROP TABLE VacancyRevisionLabelAssignment');

        // Do the same, but for activities.
        $this->addSql('CREATE TABLE ActivityLabelAssignment (activity_id INT NOT NULL, activitylabel_id INT NOT NULL, INDEX IDX_131965B847A3B8A4 (activitylabel_id), INDEX IDX_131965B881C06096 (activity_id), PRIMARY KEY (activity_id, activitylabel_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_uca1400_ai_ci` ENGINE = InnoDB COMMENT = \'\'');
        $this->addSql('ALTER TABLE ActivityLabelAssignment ADD CONSTRAINT `FK_131965B847A3B8A4` FOREIGN KEY (activitylabel_id) REFERENCES ActivityLabel (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE ActivityLabelAssignment ADD CONSTRAINT `FK_131965B881C06096` FOREIGN KEY (activity_id) REFERENCES Activity (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE Activity ADD organ_id INT DEFAULT NULL, ADD company_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE Activity ADD CONSTRAINT `FK_55026B0C979B1AD6` FOREIGN KEY (company_id) REFERENCES Company (id)');
        $this->addSql('ALTER TABLE Activity ADD CONSTRAINT `FK_55026B0CE4445171` FOREIGN KEY (organ_id) REFERENCES Organ (id)');
        $this->addSql('CREATE INDEX IDX_55026B0CE4445171 ON Activity (organ_id)');
        $this->addSql('CREATE INDEX IDX_55026B0C979B1AD6 ON Activity (company_id)');

        // Backfill the activity from its display revision (the live one, else the working head).
        $this->addSql(<<<'SQL'
            UPDATE Activity a
            JOIN ActivityRevision r ON r.id = COALESCE(a.liveRevision_id, a.currentRevision_id)
            SET a.organ_id = r.organ_id,
                a.company_id = r.company_id
        SQL);
        $this->addSql(<<<'SQL'
            INSERT INTO ActivityLabelAssignment (activity_id, activitylabel_id)
            SELECT a.id, ra.activitylabel_id
            FROM Activity a
            JOIN ActivityRevisionLabelAssignment ra
                ON ra.activityrevision_id = COALESCE(a.liveRevision_id, a.currentRevision_id)
        SQL);

        // Drop the revision-scoped columns/table.
        $this->addSql('ALTER TABLE ActivityRevisionLabelAssignment DROP FOREIGN KEY FK_AD4B45A22B53B2FF');
        $this->addSql('ALTER TABLE ActivityRevisionLabelAssignment DROP FOREIGN KEY FK_AD4B45A247A3B8A4');
        $this->addSql('DROP TABLE ActivityRevisionLabelAssignment');
        $this->addSql('ALTER TABLE ActivityRevision DROP FOREIGN KEY FK_F7309B7AE4445171');
        $this->addSql('ALTER TABLE ActivityRevision DROP FOREIGN KEY FK_F7309B7A979B1AD6');
        $this->addSql('DROP INDEX IDX_F7309B7AE4445171 ON ActivityRevision');
        $this->addSql('DROP INDEX IDX_F7309B7A979B1AD6 ON ActivityRevision');
        $this->addSql('ALTER TABLE ActivityRevision DROP organ_id, DROP company_id');

        // ── Version20260530112824: Move sign-up lists from the activity onto the activity revision. (reverse)
        // Reattach each list to the activity its owning revision belongs to, then restore the original column/FK.
        // Best-effort (as with the prior revision-split migration's down()): an activity with several revisions holds
        // a clone of each list per revision, so all clones reattach to the same activity_id and the rolled-back legacy
        // schema ends up with duplicate lists per logical list. Deleting the extras here would orphan their child rows.
        $this->addSql('ALTER TABLE SignupList DROP FOREIGN KEY FK_274D085F13741683');
        $this->addSql('DROP INDEX IDX_274D085F13741683 ON SignupList');
        $this->addSql('DROP INDEX signup_list_revision_lineage_uniq ON SignupList');
        $this->addSql('ALTER TABLE SignupList ADD activity_id INT DEFAULT NULL');
        $this->addSql(<<<'SQL'
            UPDATE SignupList sl
            JOIN ActivityRevision r ON sl.activity_revision_id = r.id
            SET sl.activity_id = r.activity_id
            SQL);
        $this->addSql('ALTER TABLE SignupList DROP lineageId, DROP activity_revision_id');
        $this->addSql('ALTER TABLE SignupList CHANGE activity_id activity_id INT NOT NULL');
        $this->addSql('ALTER TABLE SignupList ADD CONSTRAINT `FK_274D085F81C06096` FOREIGN KEY (activity_id) REFERENCES Activity (id)');
        $this->addSql('CREATE INDEX IDX_274D085F81C06096 ON SignupList (activity_id)');

        // ── Version20260529150000: Revision/approval workflow for activities + careers (split aggregates, Job->Vacancy rename, dual-actor authorship). GH-2067. (reverse)
        // Recreate the ApprovableText table
        $this->addSql('CREATE TABLE ApprovableText (id INT AUTO_INCREMENT NOT NULL, message VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_uca1400_ai_ci`, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_uca1400_ai_ci` ENGINE = InnoDB COMMENT = \'\' ');

        // Recreate the ActivityUpdateProposal table (dropped in up() when activities moved to the revision model)
        $this->addSql('CREATE TABLE ActivityUpdateProposal (id INT AUTO_INCREMENT NOT NULL, old_id INT NOT NULL, new_id INT NOT NULL, INDEX IDX_9E136D5139E6FA16 (old_id), INDEX IDX_9E136D51BD06B3B3 (new_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_uca1400_ai_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE ActivityUpdateProposal ADD CONSTRAINT FK_9E136D5139E6FA16 FOREIGN KEY (old_id) REFERENCES Activity (id)');
        $this->addSql('ALTER TABLE ActivityUpdateProposal ADD CONSTRAINT FK_9E136D51BD06B3B3 FOREIGN KEY (new_id) REFERENCES Activity (id)');

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
