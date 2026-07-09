<?php

declare(strict_types=1);

namespace App\Tests\Form\Activity;

use App\Entity\Activity\ActivityLocalisedText;
use App\Entity\Activity\ExternalSignup;
use App\Entity\Activity\SignupList;
use App\Form\Activity\SignupFieldType;
use App\Form\Activity\SignupListType;
use App\Form\Activity\SignupOptionType;
use App\Form\Application\LocalisedTextType;
use Override;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormExtensionInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Validator\Validation;

use function sprintf;

/**
 * Once a sign-up list has sign-ups the way places are allocated must not change under the people who already committed:
 * the allocation method and its per-method settings are frozen (rendered read-only, ignored on submit). The capacity is
 * deliberately left editable so seats can still be adjusted while the list is open. These tests pin both.
 */
// TypeTestCase creates an unconfigured EventDispatcher mock internally; opt out of the no-expectations notice.
#[AllowMockObjectsWithoutExpectations]
final class SignupListTypeTest extends TypeTestCase
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
     * @return list<FormTypeInterface<mixed>>
     */
    #[Override]
    protected function getTypes(): array
    {
        return [
            new SignupListType(),
            new LocalisedTextType(),
            new SignupFieldType(),
            new SignupOptionType(),
        ];
    }

    public function testAllocationMethodIsFrozenOnceTheListHasSignUps(): void
    {
        $form = $this->factory->create(
            SignupListType::class,
            $this->listWithSignUp(),
        );

        // The allocation method and every per-method setting are disabled: the deal can no longer be rewritten.
        foreach (
            [
                'allocationMethod',
                'drawCutoffRule',
                'drawCutoffAt',
                'drawAfterDurationHours',
                'externalPolicyUrl',
                'externalForceOrdering',
                'externalPaymentByExternal',
                'customMethodDescription',
            ] as $name
        ) {
            self::assertTrue(
                $this->isDisabled(
                    $form,
                    $name,
                ),
                sprintf(
                    'Expected "%s" to be frozen once the list has sign-ups.',
                    $name,
                ),
            );
        }

        // The capacity stays editable so seats can still be adjusted; likewise the safe metadata.
        foreach (
            [
                'capacity',
                'closeDate',
                'displaySubscribedNumber',
                'promoted',
            ] as $name
        ) {
            self::assertFalse(
                $this->isDisabled(
                    $form,
                    $name,
                ),
                sprintf(
                    'Expected "%s" to stay editable once the list has sign-ups.',
                    $name,
                ),
            );
        }
    }

    public function testAllocationMethodStaysEditableWhileTheListHasNoSignUps(): void
    {
        $form = $this->factory->create(
            SignupListType::class,
            $this->list(),
        );

        self::assertFalse(
            $this->isDisabled(
                $form,
                'allocationMethod',
            ),
            'A list without sign-ups must keep its allocation method editable.',
        );
    }

    private function listWithSignUp(): SignupList
    {
        $list = $this->list();
        $list->getSignUps()->add(new ExternalSignup());

        return $list;
    }

    private function list(): SignupList
    {
        $list = new SignupList();
        $list->setName(new ActivityLocalisedText(
            'Naam',
            'Name',
        ));

        return $list;
    }

    /**
     * @param FormInterface<mixed> $form
     */
    private function isDisabled(
        FormInterface $form,
        string $name,
    ): bool {
        return $form->get($name)->getConfig()->getDisabled();
    }
}
