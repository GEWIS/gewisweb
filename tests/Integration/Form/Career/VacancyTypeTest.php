<?php

declare(strict_types=1);

namespace App\Tests\Integration\Form\Career;

use App\Entity\Career\Company;
use App\Entity\Career\CompanyJobPackage;
use App\Entity\Career\Enums\VacancyCategories;
use App\Entity\Career\Vacancy;
use App\Entity\Career\VacancyRevision;
use App\Form\Career\VacancyType;
use App\Repository\Career\CompanyRepository;
use App\Repository\Career\VacancyRepository;
use App\Tests\Integration\DatabaseTestCase;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;

final class VacancyTypeTest extends DatabaseTestCase
{
    public function testACompleteVacancyIsAccepted(): void
    {
        [, $form
        ] = $this->submit();

        self::assertTrue(
            $form->isValid(),
            (string) $form->getErrors(true),
        );
    }

    public function testTheBoardDecidesWhetherAVacancyIsShown(): void
    {
        self::assertTrue($this->build(admin: true)->has('published'));
        self::assertFalse($this->build()->has('published'));
    }

    public function testAVacancyCannotCloseBeforeItOpens(): void
    {
        [, $form
        ] = $this->submit([
            'currentRevision' => [
                'startDate' => '2027-06-01',
                'endDate' => '2027-05-01',
            ],
        ]);

        self::assertFalse($form->isValid());
        self::assertCount(
            1,
            $form->get('currentRevision')->get('endDate')->getErrors(),
        );
    }

    /**
     * A vacancy is invisible once its package expires whatever its own window says, so a window that runs past the
     * package would promise something it cannot keep.
     */
    public function testAVacancyCannotStayOpenPastItsJobPackage(): void
    {
        [, $form
        ] = $this->submit(['currentRevision' => ['endDate' => '2200-01-01']]);

        self::assertFalse($form->isValid());
        self::assertCount(
            1,
            $form->get('currentRevision')->get('endDate')->getErrors(),
        );
    }

    /**
     * A package is already gone on the day it expires, while a vacancy is still shown on its closing day, so the two
     * dates being the same would advertise a vacancy that cannot be opened.
     */
    public function testAVacancyCannotCloseOnTheDayItsJobPackageExpires(): void
    {
        [, $form
        ] = $this->submit(['currentRevision' => ['endDate' => '2100-01-01']]);

        self::assertFalse($form->isValid());
        self::assertCount(
            1,
            $form->get('currentRevision')->get('endDate')->getErrors(),
        );
    }

    /**
     * Two vacancies of one company sharing a slug within a category would share a public address, and only the older
     * one would ever be reached.
     */
    public function testASlugAlreadyUsedInTheSameCategoryIsRejected(): void
    {
        [, $form
        ] = $this->submit(['slugName' => 'backend-engineer']);

        self::assertFalse($form->isValid());
        self::assertCount(
            1,
            $form->get('slugName')->getErrors(),
        );
    }

    /**
     * The same slug in another category is a different address, so nothing is in the way.
     */
    public function testTheSameSlugInAnotherCategoryIsFree(): void
    {
        [, $form
        ] = $this->submit([
            'slugName' => 'backend-engineer',
            'currentRevision' => ['category' => VacancyCategories::Internships->value],
        ]);

        self::assertTrue(
            $form->isValid(),
            (string) $form->getErrors(true),
        );
    }

    /**
     * Correcting a vacancy sold under a contract that has since run out must not force it onto another one, so the
     * package it already belongs to stays on offer even though nothing new can be posted under it.
     */
    public function testThePackageAVacancyIsAlreadySoldUnderStaysChoosable(): void
    {
        $vacancy = $this->seededVacancy('drivetrain-engineer');
        self::assertTrue($vacancy->getPackage()->isExpired());

        $choices = $this->build(
            admin: true,
            vacancy: $vacancy,
        )->get('package')->createView()->vars['choices'];

        $offered = [];
        foreach ($choices as $choice) {
            $offered[] = $choice->data;
        }

        self::assertContains(
            $vacancy->getPackage(),
            $offered,
        );
    }

    public function testTheClosingDayIsRequired(): void
    {
        [, $form
        ] = $this->submit(['currentRevision' => ['endDate' => '']]);

        self::assertFalse($form->isValid());
    }

    public function testAMalformedSlugIsRejected(): void
    {
        [, $form
        ] = $this->submit(['slugName' => 'Backend Engineer']);

        self::assertFalse($form->isValid());
        self::assertNotCount(
            0,
            $form->get('slugName')->getErrors(),
        );
    }

    public function testAnEnabledLanguageMustBeFilledIn(): void
    {
        [, $form
        ] = $this->submit([
            'currentRevision' => [
                'languageDutch' => '1',
                'name' => [
                    'valueEN' => 'Backend Engineer',
                    'valueNL' => '',
                ],
            ],
        ]);

        self::assertFalse($form->isValid());
    }

    public function testWithNoLanguageEnabledNothingCanBeSaved(): void
    {
        [, $form
        ] = $this->submit([
            'currentRevision' => [
                'languageEnglish' => null,
                'languageDutch' => null,
            ],
        ]);

        self::assertFalse($form->isValid());
    }

    /**
     * A company only gets to choose among its own running job packages, so it cannot post under somebody else's
     * contract.
     */
    public function testTheCompanysFormOnlyOffersItsOwnRunningPackages(): void
    {
        $company = $this->company('nexunt');
        $choices = $this->build(company: $company)->get('package')->createView()->vars['choices'];

        self::assertNotEmpty($choices);
        foreach ($choices as $choice) {
            $package = $choice->data;
            self::assertInstanceOf(
                CompanyJobPackage::class,
                $package,
            );
            self::assertSame(
                $company->getId(),
                $package->getCompany()->getId(),
            );
        }
    }

    /**
     * @param array<string, mixed> $overrides
     *
     * @return array{0: Vacancy, 1: FormInterface<Vacancy>}
     */
    private function submit(array $overrides = []): array
    {
        $company = $this->company('nexunt');
        $vacancy = $this->blankVacancy();
        $form = $this->build(
            admin: true,
            vacancy: $vacancy,
        );

        $revision = [
            'languageEnglish' => '1',
            'name' => [
                'valueEN' => 'Backend Engineer',
                'valueNL' => '',
            ],
            'location' => [
                'valueEN' => 'Eindhoven',
                'valueNL' => '',
            ],
            'website' => [
                'valueEN' => 'https://example.com/apply',
                'valueNL' => '',
            ],
            'description' => [
                'valueEN' => 'A role.',
                'valueNL' => '',
            ],
            'attachment' => [
                'valueEN' => '',
                'valueNL' => '',
            ],
            'category' => VacancyCategories::Jobs->value,
            'startDate' => '2026-09-01',
            'endDate' => '2027-01-31',
            'labels' => [],
        ];

        $data = $overrides + [
            'slugName' => 'a-new-role',
            'package' => (string) $this->jobPackage($company)->getId(),
            'published' => '1',
        ];
        $data['currentRevision'] = ($overrides['currentRevision'] ?? []) + $revision;

        $form->submit($data);

        return [
            $vacancy,
            $form,
        ];
    }

    /**
     * @return FormInterface<Vacancy>
     */
    private function build(
        bool $admin = false,
        ?Company $company = null,
        ?Vacancy $vacancy = null,
    ): FormInterface {
        return self::getContainer()->get(FormFactoryInterface::class)->create(
            VacancyType::class,
            $vacancy ?? $this->blankVacancy(),
            [
                'csrf_protection' => false,
                'admin' => $admin,
                'company' => $company,
            ],
        );
    }

    private function blankVacancy(): Vacancy
    {
        $vacancy = new Vacancy();
        $revision = new VacancyRevision();
        $vacancy->addRevision($revision);
        $vacancy->setCurrentRevision($revision);

        return $vacancy;
    }

    private function jobPackage(Company $company): CompanyJobPackage
    {
        foreach ($company->getPackages() as $package) {
            if (!$package instanceof CompanyJobPackage) {
                continue;
            }

            return $package;
        }

        self::fail('The seed is expected to give every company a job package.');
    }

    private function seededVacancy(string $slug): Vacancy
    {
        $vacancy = self::getContainer()->get(VacancyRepository::class)->findOneBy(['slugName' => $slug]);
        self::assertInstanceOf(
            Vacancy::class,
            $vacancy,
        );

        return $vacancy;
    }

    private function company(string $slug): Company
    {
        $company = self::getContainer()->get(CompanyRepository::class)->findOneBy(['slugName' => $slug]);
        self::assertInstanceOf(
            Company::class,
            $company,
        );

        return $company;
    }
}
