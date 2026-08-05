<?php

declare(strict_types=1);

namespace App\Form\Career;

use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

use function Symfony\Component\Translation\t;

/**
 * Who to invite to represent a company. Whether the address is free is settled by the invite service, which is the only
 * place that can see both the accounts and the pending invitations.
 *
 * @extends AbstractType<null>
 */
class CompanyRepresentativeInviteType extends AbstractType
{
    #[Override]
    public function buildForm(
        FormBuilderInterface $builder,
        array $options,
    ): void {
        $builder
            ->add(
                'name',
                TextType::class,
                [
                    'label' => t('Name'),
                    'mapped' => false,
                    'constraints' => [
                        new NotBlank(message: 'Please enter a name.'),
                        new Length(max: 255),
                    ],
                ],
            )
            ->add(
                'email',
                EmailType::class,
                [
                    'label' => t('Email address'),
                    'mapped' => false,
                    'constraints' => [
                        new NotBlank(message: 'Please enter an email address.'),
                        new Email(),
                        new Length(max: 255),
                    ],
                ],
            );
    }
}
