<?php

declare(strict_types=1);

namespace App\Form\Education;

use App\Entity\Education\Course;
use App\Repository\Education\CourseRepository;
use Doctrine\Common\Collections\Collection;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

use function strtoupper;
use function Symfony\Component\Translation\t;

/**
 * Similar courses are what a course holding nothing of its own points at. They are picked out of the archive rather
 * than typed, so a link can only ever be made to a course that exists, and never to the course itself.
 *
 * @extends AbstractType<Course>
 */
final class CourseType extends AbstractType
{
    public function __construct(private readonly CourseRepository $courseRepository)
    {
    }

    #[Override]
    public function buildForm(
        FormBuilderInterface $builder,
        array $options,
    ): void {
        $editing = true === $options['edit'];

        $builder
            ->add(
                'code',
                TextType::class,
                [
                    'label' => t('Course code'),
                    'help' => t('As the TU/e writes it, for example 2IL50.'),
                    // The code identifies a course and documents hang off it, so it is set once and then fixed.
                    'disabled' => $editing,
                    'constraints' => [
                        new NotBlank(message: 'Enter the course code.'),
                        new Length(
                            min: 5,
                            max: 9,
                            minMessage: 'A course code is at least {{ limit }} characters.',
                            maxMessage: 'A course code is at most {{ limit }} characters.',
                        ),
                        new Regex(
                            pattern: '/\A[A-Za-z0-9]+\z/',
                            message: 'A course code contains only letters and digits.',
                        ),
                        new Callback(fn (
                            ?string $code,
                            ExecutionContextInterface $context,
                        ) => $this->validateCodeIsFree(
                            $code,
                            $context,
                            $editing,
                        )),
                    ],
                ],
            )
            ->add(
                'name',
                TextType::class,
                [
                    'label' => t('Course name'),
                    'constraints' => [
                        new NotBlank(message: 'Enter the course name.'),
                        new Length(
                            max: 255,
                            maxMessage: 'A course name is at most {{ limit }} characters.',
                        ),
                    ],
                ],
            )
            ->add(
                'similarCoursesTo',
                CourseAutocompleteType::class,
                [
                    'label' => t('Similar courses'),
                    'help' => t('Shown on a course that holds nothing of its own.'),
                    'required' => false,
                    'multiple' => true,
                    'constraints' => [new Callback($this->validateSimilarCourses(...))],
                ],
            )
            ->add(
                'submit',
                SubmitType::class,
                ['label' => t('Save')],
            );
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Course::class,
            'edit' => false,
        ]);
        $resolver->setAllowedTypes(
            'edit',
            'bool',
        );
    }

    /**
     * A code identifies a course, so a new one may not take a code already in use. Editing leaves the code alone.
     */
    private function validateCodeIsFree(
        ?string $code,
        ExecutionContextInterface $context,
        bool $editing,
    ): void {
        if (
            $editing
            || null === $code
            || null === $this->courseRepository->find(strtoupper($code))
        ) {
            return;
        }

        $context->buildViolation('A course with this code already exists.')->addViolation();
    }

    /**
     * @param Collection<array-key, Course> $similar
     */
    private function validateSimilarCourses(
        Collection $similar,
        ExecutionContextInterface $context,
    ): void {
        $root = $context->getRoot()->getData();
        if (!$root instanceof Course) {
            return;
        }

        foreach ($similar as $course) {
            if ($course !== $root) {
                continue;
            }

            $context->buildViolation('A course cannot be similar to itself.')->addViolation();

            return;
        }
    }
}
