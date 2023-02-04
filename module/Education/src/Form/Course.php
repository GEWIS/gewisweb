<?php

namespace Education\Form;

use Education\Mapper\Course as CourseMapper;
use Laminas\Filter\StringToUpper;
use Laminas\Form\Element\{
    Submit,
    Text,
};
use Laminas\Form\Form;
use Laminas\I18n\Validator\Alnum;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Mvc\I18n\Translator;
use Laminas\Validator\{Callback, StringLength};

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
            ]
        );

        $this->add(
            [
                'name' => 'name',
                'type' => Text::class,
                'options' => [
                    'label' => $translator->translate('Name'),
                ],
            ]
        );

        $this->add(
            [
                'name' => 'submit',
                'type' => Submit::class,
                'attributes' => [
                    'value' => $translator->translate('Add course'),
                ],
            ]
        );
    }

    /**
     * @return array
     */
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
                            'callback' => [$this, 'isCourseCodeUnique'],
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
        ];
    }

    /**
     * Set the current code of a course.
     */
    public function setCurrentCode(string $currentCode): void
    {
        $this->currentCode = $currentCode;
    }

    public function isCourseCodeUnique(string $code): bool
    {
        if ($this->currentCode === $code) {
            return true;
        }

        return null === $this->courseMapper->find($code);
    }
}
