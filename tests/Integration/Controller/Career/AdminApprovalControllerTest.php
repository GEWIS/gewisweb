<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Career;

use App\Controller\Career\AdminApprovalController;
use App\Entity\Application\Enums\RevisionStatus;
use App\Entity\Career\CompanyRevision;
use App\Entity\Career\VacancyRevision;
use App\Entity\User\User;
use App\Security\User\SudoMode;
use App\Tests\Integration\DatabaseTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

use function strval;

/**
 * The career review screen renders two different aggregates through one template, and since the fields it shows are
 * described rather than written out, a describer that answers wrong shows a reviewer an empty panel instead of
 * failing. These render both and read what came out.
 *
 * The actions are invoked directly with the current user on the token storage, as the activity approval tests do and
 * for the same reason: the session guard force-logs-out any session with no managed-session row behind it.
 */
final class AdminApprovalControllerTest extends DatabaseTestCase
{
    public function testACompanyRevisionIsReviewedWithItsProfileContactDetailsAndLogo(): void
    {
        $revision = $this->aCompanyRevision();
        $this->authenticateAsBoardWithSudo();

        $content = strval($this->controller()->reviewCompany($revision)->getContent());

        self::assertStringContainsString(
            'Profile',
            $content,
        );
        self::assertStringContainsString(
            'Contact details',
            $content,
        );
        self::assertStringContainsString(
            'Logo',
            $content,
        );
        // The language columns are the review layout; losing them means the diff collapsed into one column.
        self::assertStringContainsString(
            'flag-icon-nl',
            $content,
        );
        self::assertStringContainsString(
            'flag-icon-en',
            $content,
        );
    }

    public function testAVacancyRevisionIsReviewedWithTheFieldsOnlyItsReviewerNeeds(): void
    {
        $revision = $this->aVacancyRevision();
        $this->authenticateAsBoardWithSudo();

        $content = strval($this->controller()->reviewVacancy($revision)->getContent());

        self::assertStringContainsString(
            'General information',
            $content,
        );
        // The owning company is on the reviewer's screen and nowhere else.
        self::assertStringContainsString(
            'Company',
            $content,
        );
        self::assertStringContainsString(
            'Posting window',
            $content,
        );
        self::assertStringContainsString(
            'Labels',
            $content,
        );
        self::assertStringContainsString(
            'Details',
            $content,
        );
        self::assertStringContainsString(
            'Contact details',
            $content,
        );
    }

    /**
     * A revision that was turned down says nothing about what visitors see, so the screen has to name the revision
     * that is still up rather than leave "Rejected" to be read as "the company is offline".
     */
    public function testARejectedRevisionStillNamesTheOneThatIsLive(): void
    {
        $revision = $this->aCompanyRevision();
        $company = $revision->getCompany();

        $live = new CompanyRevision();
        $live->setStatus(RevisionStatus::Approved);
        $live->setRevisionNumber($revision->getRevisionNumber() + 1);
        $company->addRevision($live);
        $company->setLiveRevision($live);
        $revision->setStatus(RevisionStatus::Rejected);
        $this->entityManager->persist($live);
        $this->entityManager->flush();

        $this->authenticateAsBoardWithSudo();

        self::assertStringContainsString(
            'Live: #' . $live->getRevisionNumber(),
            strval($this->controller()->reviewCompany($revision)->getContent()),
        );
    }

    private function controller(): AdminApprovalController
    {
        return self::getContainer()->get(AdminApprovalController::class);
    }

    private function aCompanyRevision(): CompanyRevision
    {
        $revision = $this->entityManager->getRepository(CompanyRevision::class)->findOneBy([]);
        self::assertInstanceOf(
            CompanyRevision::class,
            $revision,
        );

        return $revision;
    }

    private function aVacancyRevision(): VacancyRevision
    {
        $revision = $this->entityManager->getRepository(VacancyRevision::class)->findOneBy([]);
        self::assertInstanceOf(
            VacancyRevision::class,
            $revision,
        );

        return $revision;
    }

    /**
     * Opening a review screen asks a reviewer for sudo, so the grant has to be in the session the request carries.
     */
    private function authenticateAsBoardWithSudo(): void
    {
        $user = $this->entityManager->getRepository(User::class)->find(8025);
        self::assertInstanceOf(
            User::class,
            $user,
        );

        self::getContainer()->get('security.token_storage')->setToken(new UsernamePasswordToken(
            $user,
            'main',
            ['ROLE_BOARD'],
        ));

        $session = self::getContainer()->get('session.factory')->createSession();
        self::assertInstanceOf(
            FlashBagAwareSessionInterface::class,
            $session,
        );

        $request = new Request();
        $request->setSession($session);
        // A sudo grant is only read back off a session the request already carried, so the cookie has to be there.
        $request->cookies->set(
            $session->getName(),
            'test',
        );
        self::getContainer()->get('request_stack')->push($request);

        self::getContainer()->get(SudoMode::class)->grant();
    }
}
