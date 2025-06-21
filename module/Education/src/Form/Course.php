<?php

declare(strict_types=1);

namespace Education\Form;

use Education\Mapper\Course as CourseMapper;
use Laminas\Filter\StringToUpper;
use Laminas\Filter\ToNull;
use Laminas\Form\Element\Submit;
use Laminas\Form\Element\Text;
use Laminas\Form\Form;
use Laminas\I18n\Validator\Alnum;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Mvc\I18n\Translator;
use Laminas\Validator\Callback;
use Laminas\Validator\StringLength;
use Override;

use function explode;

/**
 * @psalm-suppress MissingTemplateParam
 */
class Course extends Form implements InputFilterProviderInterface
{
    private ?string $currentCode = null;

    public function __construct(
        private readonly Translator $translator,
        private readonly CourseMapper $courseMapper,
    ) {
        parent::__construct();

        $this->add(
            [
                'name' => 'code',
                'type' => Text::class,
                'options' => [
                    'label' => $translator->translate('Course code'),
                ],
            ],
        );

        $this->add(
            [
                'name' => 'name',
                'type' => Text::class,
                'options' => [
                    'label' => $translator->translate('Name'),
                ],
            ],
        );

        $this->add(
            [
                'name' => 'similar',
                'type' => Text::class,
                'options' => [
                    'label' => $translator->translate('Similar courses'),
                ],
            ],
        );

        $this->add(
            [
                'name' => 'submit',
                'type' => Submit::class,
                'attributes' => [
                    'value' => $translator->translate('Add course'),
                ],
            ],
        );
    }

    #[Override]
    public function getInputFilterSpecification(): array
    {
        return [
            'code' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => StringLength::class,
                        'options' => [
                            'min' => 5,
                            'max' => 9,
                        ],
                    ],
                    [
                        'name' => Alnum::class,
                    ],
                    [
                        'name' => Callback::class,
                        'options' => [
                            'callback' => $this->isCourseCodeUnique(...),
                            'messages' => [
                                Callback::INVALID_VALUE => $this->translator->translate(
                                    'There already exists a course with this code',
                                ),
                            ],
                        ],
                    ],
                ],
                'filters' => [
                    [
                        'name' => StringToUpper::class,
                    ],
                ],
            ],
            'name' => [
                'required' => true,
            ],
            'similar' => [
                'required' => false,
                'validators' => [
                    [
                        'name' => Callback::class,
                        'options' => [
                            'callback' => $this->areSimilarValid(...),
                            'messages' => [
                                Callback::INVALID_VALUE => $this->translator->translate(
                                    'One of the courses is not valid (either it does not exist or is the same as the '
                                    . 'current course)',
                                ),
                            ],
                        ],
                    ],
                ],
                'filters' => [
                    [
                        'name' => ToNull::class,
                    ],
                ],
            ],
        ];
    }

    /**
     * Set the current code of a course.
     */
    public function setCurrentCode(string $currentCode): void
    {
        $this->currentCode = $currentCode;
    }

    /**
     * Check if a course code is unique.
     */
    public function isCourseCodeUnique(string $code): bool
    {
        if ($this->currentCode === $code) {
            return true;
        }

        return null === $this->courseMapper->find($code);
    }

    /**
     * Check if the similar courses are valid.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    public function areSimilarValid(
        string $similar,
        array $context = [],
    ): bool {
        $code = $context['code'];
        $courses = explode(',', $similar);

        foreach ($courses as $course) {
            if (!$this->isSimilarValid($course, $code)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if a similar course is valid.
     */
    public function isSimilarValid(
        string $similar,
        string $code,
    ): bool {
        return $similar !== $code
            && null !== $this->courseMapper->find($similar);
    }
}
