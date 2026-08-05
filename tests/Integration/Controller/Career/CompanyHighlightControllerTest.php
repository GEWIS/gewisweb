<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Career;

use App\Controller\Career\CompanyHighlightController;
use App\Entity\Career\CompanyHighlightPackage;
use App\Entity\Career\Vacancy;
use App\Entity\User\CompanyUser;
use App\Form\Career\HighlightSelectionType;
use App\Repository\Career\CompanyHighlightPackageRepository;
use App\Repository\User\CompanyUserRepository;
use App\Tests\Integration\DatabaseTestCase;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

final class CompanyHighlightControllerTest extends DatabaseTestCase
{
    public function testACompanyWithAPackageIsOfferedItsLiveVacancies(): void
    {
        $companyUser = $this->signIn();

        $response = $this->controller()->index(
            new Request(),
            $companyUser,
        );

        self::assertSame(
            Response::HTTP_OK,
            $response->getStatusCode(),
        );
        $content = (string) $response->getContent();
        self::assertStringContainsString(
            'Backend Engineer',
            $content,
        );
        // Its posting window has closed, so it cannot be put on the career page.
        self::assertStringNotContainsString(
            'Platform Engineering Internship',
            $content,
        );
    }

    public function testACompanyWithoutAPackageIsToldWhereToAsk(): void
    {
        $companyUser = $this->signIn('recruitment@delta-robotics.example.com');

        $response = $this->controller()->index(
            new Request(),
            $companyUser,
        );

        self::assertStringContainsString(
            'do not have a highlight package',
            (string) $response->getContent(),
        );
    }

    /**
     * The choice list is drawn from what is live at that moment, so a submission naming something else is answering a
     * list that has moved on and is refused rather than trusted.
     */
    public function testAVacancyThatIsNotEligibleIsRefused(): void
    {
        $companyUser = $this->signIn();
        $package = $this->package();

        $form = self::getContainer()->get(FormFactoryInterface::class)->create(
            HighlightSelectionType::class,
            $package,
            [
                'csrf_protection' => false,
                'company' => $companyUser->getCompany(),
            ],
        );
        $form->submit(['vacancies' => [(string) $this->vacancy('data-science-internship')->getId()]]);

        self::assertFalse($form->isValid());
    }

    public function testAnEligibleVacancyIsAccepted(): void
    {
        $companyUser = $this->signIn();
        $package = $this->package();

        $form = self::getContainer()->get(FormFactoryInterface::class)->create(
            HighlightSelectionType::class,
            $package,
            [
                'csrf_protection' => false,
                'company' => $companyUser->getCompany(),
            ],
        );
        $form->submit(['vacancies' => [(string) $this->vacancy('backend-engineer')->getId()]]);

        self::assertTrue(
            $form->isValid(),
            (string) $form->getErrors(true),
        );
        self::assertCount(
            1,
            $package->getVacancies(),
        );
    }

    private function package(): CompanyHighlightPackage
    {
        $packages = self::getContainer()->get(CompanyHighlightPackageRepository::class)->findActive();
        self::assertNotEmpty($packages);

        return $packages[0];
    }

    private function vacancy(string $slug): Vacancy
    {
        $vacancy = $this->entityManager->getRepository(Vacancy::class)->findOneBy(['slugName' => $slug]);
        self::assertInstanceOf(
            Vacancy::class,
            $vacancy,
        );

        return $vacancy;
    }

    private function signIn(string $email = 'recruitment@nexunt.example.com'): CompanyUser
    {
        $companyUser = self::getContainer()->get(CompanyUserRepository::class)->loadUserByIdentifier($email);
        self::assertInstanceOf(
            CompanyUser::class,
            $companyUser,
        );

        self::getContainer()->get('security.token_storage')->setToken(new UsernamePasswordToken(
            $companyUser,
            'company',
            ['ROLE_COMPANY_USER'],
        ));

        $session = self::getContainer()->get('session.factory')->createSession();
        self::assertInstanceOf(
            FlashBagAwareSessionInterface::class,
            $session,
        );
        $request = new Request();
        $request->setSession($session);
        self::getContainer()->get('request_stack')->push($request);

        return $companyUser;
    }

    private function controller(): CompanyHighlightController
    {
        return self::getContainer()->get(CompanyHighlightController::class);
    }
}
