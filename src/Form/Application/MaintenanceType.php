<?php

declare(strict_types=1);

namespace App\Form\Application;

use App\Entity\Application\Enums\MaintenanceStatus;
use App\Entity\Application\MaintenanceWindow;
use App\Repository\Application\MaintenanceWindowRepository;
use DateTimeImmutable;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

use function Symfony\Component\Translation\t;

/**
 * @extends AbstractType<MaintenanceWindow>
 */
class MaintenanceType extends AbstractType
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly MaintenanceWindowRepository $repository,
    ) {
    }

    #[Override]
    public function buildForm(
        FormBuilderInterface $builder,
        array $options,
    ): void {
        $builder
            ->add(
                'status',
                EnumType::class,
                [
                    'label' => t('Mode'),
                    'help' => t(
                        'Read-only blocks changes for non-admins; full shows the maintenance page. Admins always keep full access.', // phpcs:ignore Generic.Files.LineLength.TooLong -- user-visible strings should not be split
                    ),
                    'class' => MaintenanceStatus::class,
                    'choices' => [
                        MaintenanceStatus::ReadOnly,
                        MaintenanceStatus::Full,
                    ],
                ],
            )
            ->add(
                'startsAt',
                DateTimeType::class,
                [
                    'label' => t('From'),
                    'help' => t('Leave empty to start immediately.'),
                    'required' => false,
                    'widget' => 'single_text',
                    'input' => 'datetime_immutable',
                ],
            )
            ->add(
                'endsAt',
                DateTimeType::class,
                [
                    'label' => t('Until'),
                    'help' => t('Leave empty to keep it on until you turn it off.'),
                    'required' => false,
                    'widget' => 'single_text',
                    'input' => 'datetime_immutable',
                ],
            );

        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            $this->validateWindow(...),
        );
    }

    public function validateWindow(FormEvent $event): void
    {
        $window = $event->getData();
        if (!$window instanceof MaintenanceWindow) {
            return;
        }

        $startsAt = $window->getStartsAt();
        $endsAt = $window->getEndsAt();
        if (
            $startsAt instanceof DateTimeImmutable
            && $endsAt instanceof DateTimeImmutable
            && $endsAt <= $startsAt
        ) {
            $event->getForm()->get('endsAt')->addError(new FormError(
                $this->translator->trans('The end must be after the start.'),
            ));

            return;
        }

        if ([] === $this->repository->findOverlapping($window)) {
            return;
        }

        $event->getForm()->addError(new FormError(
            $this->translator->trans('This window overlaps an existing maintenance window.'),
        ));
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => MaintenanceWindow::class]);
    }
}
