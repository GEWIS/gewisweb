<?php

declare(strict_types=1);

namespace App\Form\Education;

use App\Entity\Education\Course;
use App\Entity\User\Enums\UserRoles;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\Autocomplete\Form\AsEntityAutocompleteField;
use Symfony\UX\Autocomplete\Form\BaseEntityAutocompleteType;

use function sprintf;
use function Symfony\Component\Translation\t;

/**
 * Picking courses out of the archive, which runs to thousands of them. A course is known by its code at least as well
 * as by its name, so both are searchable and both are shown.
 *
 * @extends AbstractType<Course>
 */
#[AsEntityAutocompleteField(alias: 'course')]
final class CourseAutocompleteType extends AbstractType
{
    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => Course::class,
            'searchable_fields' => [
                'code',
                'name',
            ],
            'choice_label' => static fn (Course $course): string => sprintf(
                '%s - %s',
                $course->getCode(),
                $course->getName(),
            ),
            'placeholder' => t('Search by course code or name'),
            'security' => UserRoles::Board->value,
        ]);
    }

    #[Override]
    public function getParent(): string
    {
        return BaseEntityAutocompleteType::class;
    }
}
