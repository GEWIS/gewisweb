<?php

declare(strict_types=1);

namespace App\Form\Application;

use App\Entity\Application\Announcement;
use App\Entity\Application\ApplicationLocalisedText;
use App\Entity\Application\Enums\AlertTypes;
use App\Entity\Application\LocalisedText;
use DateTimeImmutable;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

use function in_array;
use function Symfony\Component\Translation\t;

/**
 * @extends AbstractType<Announcement>
 */
class AnnouncementType extends AbstractType
{
    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    #[Override]
    public function buildForm(
        FormBuilderInterface $builder,
        array $options,
    ): void {
        $builder
            ->add(
                'level',
                EnumType::class,
                [
                    'label' => t('Level'),
                    'class' => AlertTypes::class,
                ],
            )
            ->add(
                'title',
                LocalisedTextType::class,
                [
                    'label' => false,
                    'data_class' => ApplicationLocalisedText::class,
                ],
            )
            ->add(
                'body',
                LocalisedTextType::class,
                [
                    'label' => false,
                    'multiline' => true,
                    'data_class' => ApplicationLocalisedText::class,
                ],
            )
            ->add(
                'sticky',
                CheckboxType::class,
                [
                    'label' => t('Keep it pinned as a banner at the top of every page'),
                    'required' => false,
                    'mapped' => false,
                ],
            )
            ->add(
                'endsAt',
                DateTimeType::class,
                [
                    'label' => t('Show until'),
                    'required' => false,
                    'widget' => 'single_text',
                    'input' => 'datetime_immutable',
                    'mapped' => false,
                ],
            );

        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            $this->normaliseAndValidate(...),
        );
    }

    public function normaliseAndValidate(FormEvent $event): void
    {
        $announcement = $event->getData();
        if (!$announcement instanceof Announcement) {
            return;
        }

        $form = $event->getForm();
        $this->requireEnglish(
            $form->get('title'),
            $announcement->getTitle(),
            $this->translator->trans('Enter an English title.'),
        );
        $this->requireEnglish(
            $form->get('body'),
            $announcement->getBody(),
            $this->translator->trans('Enter an English message.'),
        );

        $this->validateStickyEnd($form);
    }

    /**
     * The English value is the required baseline (the Dutch side falls back to it), so an empty value is normalised to
     * null and a missing English value is rejected.
     *
     * @param FormInterface<array<string, mixed>> $field
     */
    private function requireEnglish(
        FormInterface $field,
        LocalisedText $text,
        string $message,
    ): void {
        if ('' === $text->getValueNL()) {
            $text->updateValueNL(null);
        }

        if (
            !in_array(
                $text->getValueEN(),
                [
                    '',
                    null,
                ],
                true,
            )
        ) {
            return;
        }

        $text->updateValueEN(null);
        $field->get('valueEN')->addError(new FormError($message));
    }

    /**
     * @param FormInterface<array<string, mixed>> $form
     */
    private function validateStickyEnd(FormInterface $form): void
    {
        if (true !== $form->get('sticky')->getData()) {
            return;
        }

        $endsAt = $form->get('endsAt')->getData();
        if (!$endsAt instanceof DateTimeImmutable) {
            $form->get('endsAt')->addError(new FormError(
                $this->translator->trans('A pinned announcement needs an end date.'),
            ));

            return;
        }

        if ($endsAt > new DateTimeImmutable()) {
            return;
        }

        $form->get('endsAt')->addError(new FormError(
            $this->translator->trans('The end date must be in the future.'),
        ));
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Announcement::class]);
    }
}
