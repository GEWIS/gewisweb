<?php

declare(strict_types=1);

namespace Education\Form\Fieldset;

use Application\Model\Enums\Languages;
use Education\Model\Enums\ExamTypes;
use Laminas\Filter\StringToUpper;
use Laminas\Form\Element\Checkbox;
use Laminas\Form\Element\Date;
use Laminas\Form\Element\Hidden;
use Laminas\Form\Element\Select;
use Laminas\Form\Element\Text;
use Laminas\Form\Fieldset;
use Laminas\I18n\Validator\Alnum;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Mvc\I18n\Translator;
use Laminas\Validator\Callback;
use Laminas\Validator\Date as DateValidator;
use Laminas\Validator\File\Exists;
use Laminas\Validator\InArray;
use Laminas\Validator\Regex;
use Laminas\Validator\StringLength;

class Exam extends Fieldset implements InputFilterProviderInterface
{
    /** @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingTraversableTypeHintSpecification */
    protected array $config;

    public function __construct(private readonly Translator $translator)
    {
        parent::__construct('exam');

        $this->add(
            [
                'name' => 'file',
                'type' => Hidden::class,
            ],
        );

        $this->add(
            [
                'name' => 'course',
                'type' => Text::class,
                'options' => [
                    'label' => $this->translator->translate('Course code'),
                ],
            ],
        );

        $this->add(
            [
                'name' => 'date',
                'type' => Date::class,
                'options' => [
                    'label' => $this->translator->translate('Exam date'),
                    'format' => 'Y-m-d',
                ],
            ],
        );

        $this->add(
            [
                'name' => 'examType',
                'type' => Select::class,
                'options' => [
                    'label' => $this->translator->translate('Type'),
                    'value_options' => [
                        ExamTypes::Final->value => ExamTypes::Final->getName($this->translator),
                        ExamTypes::Interim->value => ExamTypes::Interim->getName($this->translator),
                        ExamTypes::Answers->value => ExamTypes::Answers->getName($this->translator),
                        ExamTypes::Other->value => ExamTypes::Other->getName($this->translator),
                    ],
                ],
            ],
        );

        $this->add(
            [
                'name' => 'language',
                'type' => Select::class,
                'options' => [
                    'label' => $this->translator->translate('Language'),
                    'value_options' => [
                        Languages::EN->value => Languages::EN->getName($this->translator),
                        Languages::NL->value => Languages::NL->getName($this->translator),
                    ],
                ],
            ],
        );

        $this->add(
            [
                'name' => 'scanned',
                'type' => Checkbox::class,
                'options' => [
                    'label' => $this->translator->translate('Scanned?'),
                ],
            ],
        );
    }

    /**
     * Set the configuration.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    public function setConfig(array $config): void
    {
        $this->config = $config['education_temp'];
    }

    public function getInputFilterSpecification(): array
    {
        $dir = $this->config['upload_exam_dir'];

        return [
            'file' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => Regex::class,
                        'options' => [
                            'pattern' => '/.+\.pdf$/',
                        ],
                    ],
                    [
                        'name' => Callback::class,
                        'options' => [
                            'callback' => static function ($value) use ($dir) {
                                $validator = new Exists(
                                    [
                                        'directory' => $dir,
                                    ],
                                );

                                return $validator->isValid($value);
                            },
                        ],
                    ],
                ],
            ],
            'course' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => StringLength::class,
                        'options' => [
                            'min' => 5,
                            'max' => 6,
                        ],
                    ],
                    [
                        'name' => Alnum::class,
                    ],
                ],
                'filters' => [
                    [
                        'name' => StringToUpper::class,
                    ],
                ],
            ],
            'date' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => DateValidator::class,
                    ],
                ],
            ],
            'examType' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => InArray::class,
                        'options' => [
                            'haystack' => ExamTypes::values(),
                        ],
                    ],
                ],
            ],
            'language' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => InArray::class,
                        'options' => [
                            'haystack' => Languages::values(),
                        ],
                    ],
                ],
            ],
        ];
    }
}
