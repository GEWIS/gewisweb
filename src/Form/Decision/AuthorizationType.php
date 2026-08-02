<?php

declare(strict_types=1);

namespace App\Form\Decision;

use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

use function Symfony\Component\Translation\t;

/**
 * The GMM authorization form: the recipient is filled by the member typeahead, and the agreement checkbox is the
 * actual proxy statement.
 *
 * @extends AbstractType<array<string, mixed>>
 */
class AuthorizationType extends AbstractType
{
    #[Override]
    public function buildForm(
        FormBuilderInterface $builder,
        array $options,
    ): void {
        $builder
            ->add(
                'recipient',
                HiddenType::class,
                [
                    'constraints' => [
                        new NotBlank(message: 'Pick the member you want to authorize.'),
                    ],
                ],
            )
            ->add(
                'agreement',
                CheckboxType::class,
                [
                    'label' => t('I authorize the selected member to vote on my behalf during the upcoming GMM.'),
                    'constraints' => [
                        new NotBlank(message: 'You have to agree to the authorization statement.'),
                    ],
                ],
            );
    }
}
