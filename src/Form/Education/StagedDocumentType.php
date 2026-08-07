<?php

declare(strict_types=1);

namespace App\Form\Education;

use App\Entity\Application\Enums\Languages;
use App\Entity\Education\CourseDocumentStaging;
use App\Entity\Education\Enums\CourseDocumentTypes;
use App\Entity\Education\Enums\ExamTypes;
use App\Repository\Education\CourseRepository;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

use function strtoupper;
use function Symfony\Component\Translation\t;
use function trim;

/**
 * Everything here starts out guessed from the filename, so the row is a correction rather than data entry. The course
 * is a plain code field rather than a picker, because the guess is usually already right.
 *
 * @extends AbstractType<CourseDocumentStaging>
 */
final class StagedDocumentType extends AbstractType
{
    public function __construct(private readonly CourseRepository $courseRepository)
    {
    }

    #[Override]
    public function buildForm(
        FormBuilderInterface $builder,
        array $options,
    ): void {
        $builder
            ->add(
                'courseCode',
                TextType::class,
                [
                    'label' => t('Course'),
                    'required' => false,
                    'constraints' => [new Callback($this->validateCourseExists(...))],
                ],
            )
            ->add(
                'type',
                EnumType::class,
                [
                    'label' => t('Kind'),
                    'class' => CourseDocumentTypes::class,
                    'choice_label' => static fn (CourseDocumentTypes $type) => $type->label(),
                ],
            )
            ->add(
                'examType',
                EnumType::class,
                [
                    'label' => t('Exam type'),
                    'class' => ExamTypes::class,
                    'required' => false,
                    'placeholder' => false,
                    'choice_label' => static fn (ExamTypes $type) => $type->label(),
                ],
            )
            ->add(
                'author',
                TextType::class,
                [
                    'label' => t('Author'),
                    'required' => false,
                ],
            )
            ->add(
                'date',
                DateType::class,
                [
                    'label' => t('Date'),
                    'widget' => 'single_text',
                    'constraints' => [new NotNull(message: 'Enter the date of this document.')],
                ],
            )
            ->add(
                'language',
                EnumType::class,
                [
                    'label' => t('Language'),
                    'class' => Languages::class,
                    'choice_label' => static fn (Languages $language) => $language->label(),
                ],
            )
            ->add(
                'scanned',
                CheckboxType::class,
                [
                    'label' => t('Scanned'),
                    'help' => t('Rendered at a higher resolution, which a scan needs to stay legible.'),
                    'required' => false,
                ],
            );
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => CourseDocumentStaging::class]);
    }

    /**
     * A document has to be filed under a course that exists, since the course is what the archive is organised by.
     */
    private function validateCourseExists(
        ?string $code,
        ExecutionContextInterface $context,
    ): void {
        $code = null !== $code
            ? strtoupper(trim($code))
            : '';

        if ('' === $code) {
            $context->buildViolation('Enter the course this belongs to.')->addViolation();

            return;
        }

        if (null !== $this->courseRepository->find($code)) {
            return;
        }

        $context->buildViolation('There is no course with code "{{ code }}".')
            ->setParameter(
                '{{ code }}',
                $code,
            )
            ->addViolation();
    }
}
