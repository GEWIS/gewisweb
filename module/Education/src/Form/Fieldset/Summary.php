<?php

declare(strict_types=1);

namespace Education\Form\Fieldset;

use Application\Model\Enums\Languages;
use Laminas\Filter\StringToUpper;
use Laminas\Filter\ToNull;
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

class Summary extends Fieldset implements InputFilterProviderInterface
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
                    'label' => $this->translator->translate('Summary date'),
                    'format' => 'Y-m-d',
                ],
            ],
        );

        $this->add(
            [
                'name' => 'author',
                'type' => Text::class,
                'options' => [
                    'label' => $this->translator->translate('Author'),
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
                        Languages::English->getLangParam() => Languages::English->getName($this->translator),
                        Languages::Dutch->getLangParam() => Languages::Dutch->getName($this->translator),
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
        $dir = $this->config['upload_summary_dir'];

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
            'author' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => StringLength::class,
                        'options' => [
                            'min' => 3,
                            'max' => 150,
                        ],
                    ],
                ],
                'filters' => [
                    [
                        'name' => ToNull::class,
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
