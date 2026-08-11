<?php

declare(strict_types=1);

namespace App\Tests\Form\Career;

use App\Entity\Career\Company;
use App\Entity\Career\CompanyRevision;
use App\Form\Application\LocalisedTextType;
use App\Form\Career\CompanyRevisionType;
use App\Form\Career\CompanyType;
use App\Util\Application\SlugRule;
use Override;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormExtensionInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Validation;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * One form serves the board and the company itself. What separates them is not a permission check inside the form but
 * which fields exist at all: how a company is identified and whether it is shown is the board's call, so a
 * representative's form simply has nowhere to put those values.
 */
// TypeTestCase creates an unconfigured EventDispatcher mock internally; opt out of the no-expectations notice.
#[AllowMockObjectsWithoutExpectations]
final class CompanyTypeTest extends TypeTestCase
{
    /**
     * @return list<FormExtensionInterface>
     */
    #[Override]
    protected function getExtensions(): array
    {
        return [
            new ValidatorExtension(Validation::createValidator()),
        ];
    }

    /**
     * @return list<CompanyType|CompanyRevisionType|LocalisedTextType>
     */
    #[Override]
    protected function getTypes(): array
    {
        return [
            new CompanyType(self::createStub(TranslatorInterface::class)),
            new CompanyRevisionType(),
            new LocalisedTextType(),
        ];
    }

    public function testTheBoardDecidesTheNameTheSlugAndWhetherItIsShown(): void
    {
        $form = $this->factory->create(
            CompanyType::class,
            new Company(),
            ['admin' => true],
        );

        foreach (
            [
                'name',
                'slugName',
                'published',
            ] as $field
        ) {
            self::assertTrue(
                $form->has($field),
                $field,
            );
        }
    }

    public function testACompanyEditingItselfHasNoneOfThose(): void
    {
        $form = $this->factory->create(
            CompanyType::class,
            new Company(),
        );

        foreach (
            [
                'name',
                'slugName',
                'published',
            ] as $field
        ) {
            self::assertFalse(
                $form->has($field),
                $field,
            );
        }

        self::assertTrue($form->has('currentRevision'));
    }

    public function testTheContentAndBothLogoUploadsAreOfferedToBothAudiences(): void
    {
        $revision = $this->factory->create(
            CompanyType::class,
            new Company(),
        )->get('currentRevision');

        foreach (
            [
                'slogan',
                'website',
                'description',
                'contactName',
                'contactEmail',
                'contactPhone',
                'contactAddress',
                'squareLogoFile',
                'bannerLogoFile',
                'languageDutch',
                'languageEnglish',
            ] as $field
        ) {
            self::assertTrue(
                $revision->has($field),
                $field,
            );
        }
    }

    public function testBothLogosAreDemandedUntilTheProfileCarriesThem(): void
    {
        $company = new Company();
        $revision = new CompanyRevision();
        $company->addRevision($revision);
        $company->setCurrentRevision($revision);

        foreach (
            [
                'squareLogoFile',
                'bannerLogoFile',
            ] as $field
        ) {
            self::assertTrue(
                $this->logoField(
                    $company,
                    $field,
                )->getConfig()->getRequired(),
                $field,
            );
        }

        $revision->setSquareLogo('career/1/images/square.png');
        $revision->setBannerLogo('career/1/images/banner.png');

        foreach (
            [
                'squareLogoFile',
                'bannerLogoFile',
            ] as $field
        ) {
            self::assertFalse(
                $this->logoField(
                    $company,
                    $field,
                )->getConfig()->getRequired(),
                $field,
            );
        }
    }

    /**
     * @return FormInterface<mixed>
     */
    private function logoField(
        Company $company,
        string $field,
    ): FormInterface {
        return $this->factory->create(
            CompanyType::class,
            $company,
        )->get('currentRevision')->get($field);
    }

    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function slugs(): iterable
    {
        yield 'a plain slug' => [
            'nexunt',
            true,
        ];

        yield 'with a hyphen and a digit' => [
            'delta-robotics-2',
            true,
        ];

        yield 'with a capital' => [
            'Nexunt',
            false,
        ];

        yield 'with a space' => [
            'delta robotics',
            false,
        ];

        yield 'starting with a digit' => [
            '3m',
            false,
        ];
    }

    /**
     * The slug field carries the same rule the routes and the migration use, so a company cannot be given a name it
     * could never be reached under.
     */
    #[DataProvider('slugs')]
    public function testTheSlugFieldEnforcesTheSlugRule(
        string $slug,
        bool $acceptable,
    ): void {
        $constraints = $this->factory->create(
            CompanyType::class,
            new Company(),
            ['admin' => true],
        )->get('slugName')->getConfig()->getOption('constraints');

        $regex = null;
        foreach ($constraints as $constraint) {
            if (!$constraint instanceof Regex) {
                continue;
            }

            $regex = $constraint;
        }

        self::assertNotNull($regex);
        self::assertSame(
            SlugRule::PATTERN,
            $regex->pattern,
        );
        self::assertSame(
            $acceptable,
            SlugRule::matches($slug),
        );
    }
}
