<?php

namespace Activity\Form;

use DateTime;
use DomainException;
use Exception;
use Laminas\Form\Element\{
    Checkbox,
    Collection,
    DateTime as DateTimeElement,
    MultiCheckbox,
    Select,
    Submit,
    Text,
    Textarea,
};
use Laminas\Form\Form;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Mvc\I18n\Translator;
use Laminas\Validator\{
    Callback,
    NotEmpty,
};

class Activity extends Form implements InputFilterProviderInterface
{
    /**
     * @var Translator
     */
    protected Translator $translator;

    /**
     * @param Translator $translator
     */
    public function __construct(Translator $translator)
    {
        parent::__construct('activity');
        $this->translator = $translator;

        $this->setAttribute('method', 'post');

        $organOptions = [0 => $this->translator->translate('No organ')];
        $companyOptions = [0 => $this->translator->translate('No Company')];

        $this->add(
            [
                'name' => 'organ',
                'type' => Select::class,
                'options' => [
                    'value_options' => $organOptions,
                ],
            ]
        );

        $this->add(
            [
                'name' => 'company',
                'type' => Select::class,
                'options' => [
                    'value_options' => $companyOptions,
                ],
            ]
        );

        $this->add(
            [
                'name' => 'beginTime',
                'type' => DateTimeElement::class,
                'options' => [
                    'format' => 'Y/m/d H:i',
                ],
            ]
        );

        $this->add(
            [
                'name' => 'endTime',
                'type' => DateTimeElement::class,
                'options' => [
                    'format' => 'Y/m/d H:i',
                ],
            ]
        );

        $this->add(
            [
                'name' => 'language_dutch',
                'type' => Checkbox::class,
                'options' => [
                    'checked_value' => 1,
                    'unchecked_value' => 0,
                ],
            ]
        );

        $this->add(
            [
                'name' => 'language_english',
                'type' => Checkbox::class,
                'options' => [
                    'checked_value' => 1,
                    'unchecked_value' => 0,
                ],
            ]
        );

        $this->add(
            [
                'name' => 'name',
                'type' => Text::class,
            ]
        );

        $this->add(
            [
                'name' => 'nameEn',
                'type' => Text::class,
            ]
        );

        $this->add(
            [
                'name' => 'location',
                'type' => Text::class,
            ]
        );

        $this->add(
            [
                'name' => 'locationEn',
                'type' => Text::class,
            ]
        );

        $this->add(
            [
                'name' => 'costs',
                'type' => Text::class,
            ]
        );
        $this->add(
            [
                'name' => 'costsEn',
                'type' => Text::class,
            ]
        );

        $this->add(
            [
                'name' => 'description',
                'type' => Textarea::class,
            ]
        );

        $this->add(
            [
                'name' => 'descriptionEn',
                'type' => Textarea::class,
            ]
        );

        $this->add(
            [
                'name' => 'isMyFuture',
                'type' => Checkbox::class,
                'options' => [
                    'checked_value' => 1,
                    'unchecked_value' => 0,
                ],
            ]
        );

        $this->add(
            [
                'name' => 'requireGEFLITST',
                'type' => Checkbox::class,
                'options' => [
                    'checked_value' => 1,
                    'unchecked_value' => 0,
                ],
            ]
        );

        $this->add(
            [
                'name' => 'categories',
                'type' => MultiCheckbox::class,
                'options' => [
                    'value_options' => [],
                ],
            ]
        );

        $this->add(
            [
                'name' => 'signupLists',
                'type' => Collection::class,
                'options' => [
                    'count' => 0,
                    'should_create_template' => true,
                    'template_placeholder' => '__signuplist__',
                    'allow_add' => true,
                    'target_element' => new SignupList($translator),
                ],
            ]
        );

        $this->add(
            [
                'name' => 'submit',
                'type' => Submit::class,
                'attributes' => [
                    'value' => 'Create',
                ],
            ]
        );
    }

    /**
     * @param array $organs
     * @param array $companies
     * @param array $categories
     *
     * @return Activity
     */
    public function setAllOptions(array $organs, array $companies, array $categories): static
    {
        $organOptions = $this->get('organ')->getValueOptions();
        foreach ($organs as $organ) {
            $organOptions[$organ->getId()] = $organ->getAbbr();
        }

        $this->get('organ')->setValueOptions($organOptions);

        $companyOptions = $this->get('company')->getValueOptions();
        foreach ($companies as $company) {
            $companyOptions[$company->getId()] = $company->getName();
        }

        $this->get('company')->setValueOptions($companyOptions);

        $categoryOptions = [];
        foreach ($categories as $category) {
            $categoryOptions[$category->getId()] = $category->getName();
        }

        $this->get('categories')->setValueOptions($categoryOptions);

        return $this;
    }

    /**
     * Check if a certain date is before the end date of the activity.
     *
     * @param DateTime $value
     * @param array $context
     *
     * @return bool
     */
    public static function beforeEndTime($value, $context = [])
    {
        try {
            $endTime = $context['endTime'];
            $endTime = isset($endTime) ? new DateTime($endTime) : new DateTime('now');

            return $value <= $endTime;
        } catch (Exception $e) {
            // An exception is an indication that one of the times was not valid
            return false;
        }
    }

    /**
     * Checks if a certain date is before the begin date of the activity.
     *
     * @param DateTime $value
     * @param array $context
     *
     * @return bool
     */
    public static function beforeBeginTime($value, $context = [])
    {
        try {
            $beginTime = isset($context['beginTime']) ? new DateTime($context['beginTime']) : new DateTime('now');

            return $value <= $beginTime;
        } catch (Exception $e) {
            // An exception is an indication that one of the DateTimes was not valid
            return false;
        }
    }

    /**
     * Validate the form.
     *
     * @return bool
     *
     * @throws DomainException
     */
    public function isValid()
    {
        $valid = parent::isValid();

        /*
         * This might seem like a bit of a hack, but this is probably the only way Laminas allows us to do this.
         */
        if (isset($this->data['language_dutch']) && isset($this->data['language_english'])) {
            // Check for each SignupList whether the required fields have data.
            foreach ($this->get('signupLists')->getFieldSets() as $signupList) {
                // Check the Dutch name of the SignupLists.
                if ($this->data['language_dutch']) {
                    if (!(new NotEmpty())->isValid($signupList->get('name')->getValue())) {
                        $signupList->get('name')->setMessages(
                            [
                                $this->translator->translate('Value is required and can\'t be empty'),
                            ],
                        );
                        $valid = false;
                    }
                }

                // Check the English name of the SignupLists.
                if ($this->data['language_english']) {
                    if (!(new NotEmpty())->isValid($signupList->get('nameEn')->getValue())) {
                        $signupList->get('nameEn')->setMessages(
                            [
                                $this->translator->translate('Value is required and can\'t be empty'),
                            ],
                        );
                        $valid = false;
                    }
                }

                // Check the SignupFields of the SignupLists.
                foreach ($signupList->get('fields')->getFieldSets() as $field) {
                    // Check the Dutch name of the SignupField and the "Options" option.
                    if ($this->data['language_dutch']) {
                        if (!(new NotEmpty())->isValid($field->get('name')->getValue())) {
                            $field->get('name')->setMessages(
                                [
                                    $this->translator->translate('Value is required and can\'t be empty'),
                                ],
                            );
                            $valid = false;
                        }

                        if (
                            '3' === $field->get('type')->getValue()
                            && !(new NotEmpty())->isValid($field->get('options')->getValue())
                        ) {
                            $field->get('options')->setMessages(
                                [
                                    $this->translator->translate('Value is required and can\'t be empty'),
                                ],
                            );
                            $valid = false;
                        }
                    }

                    // Check the English name of the SignupField and the "Options" option.
                    if ($this->data['language_english']) {
                        if (!(new NotEmpty())->isValid($field->get('nameEn')->getValue())) {
                            $field->get('nameEn')->setMessages(
                                [
                                    $this->translator->translate('Value is required and can\'t be empty'),
                                ],
                            );
                            $valid = false;
                        }

                        if (
                            '3' === $field->get('type')->getValue()
                            && !(new NotEmpty())->isValid($field->get('optionsEn')->getValue())
                        ) {
                            $field->get('optionsEn')->setMessages(
                                [
                                    $this->translator->translate('Value is required and can\'t be empty'),
                                ],
                            );
                            $valid = false;
                        }
                    }
                }
            }
        }

        $this->isValid = $valid;

        return $valid;
    }

    /**
     * Get the input filter. Will generate a different inputfilter depending on if the Dutch and/or English language
     * is set.
     *
     * @return array
     */
    public function getInputFilterSpecification()
    {
        $filter = [
            'organ' => [
                'required' => true,
            ],
            'company' => [
                'required' => true,
            ],
            'beginTime' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => Callback::class,
                        'options' => [
                            'messages' => [
                                Callback::INVALID_VALUE => $this->translator->translate('The activity must start before it ends.'),
                            ],
                            'callback' => [$this, 'beforeEndTime'],
                        ],
                    ],
                ],
            ],
            'endTime' => [
                'required' => true,
            ],
            'categories' => [
                'required' => false,
            ],
        ];

        if (
            isset($this->data['language_english'])
            && $this->data['language_english']
        ) {
            $filter += $this->inputFilterEnglish();
        }

        if (
            isset($this->data['language_dutch'])
            && $this->data['language_dutch']
        ) {
            $filter += $this->inputFilterDutch();
        }
        // One of the language_dutch or language_english needs to set. If not, display a message at both, indicating that
        // they need to be set

        if (
            (isset($this->data['language_dutch']) && !$this->data['language_dutch'])
            && (isset($this->data['language_english']) && !$this->data['language_english'])
        ) {
            unset($this->data['language_dutch'], $this->data['language_english']);

            $filter += [
                'language_dutch' => [
                    'required' => true,
                ],
                'language_english' => [
                    'required' => true,
                ],
            ];
        }

        return $filter;
    }

    /***
     * Add  the input filter for the English language
     *
     * @return array
     */
    public function inputFilterEnglish()
    {
        return $this->inputFilterGeneric('En');
    }

    /**
     * Build a generic input filter.
     *
     * @input string $languagePostFix Postfix that is used for language fields to indicate that a field belongs to that
     * language
     *
     * @return array
     */
    protected function inputFilterGeneric($languagePostFix)
    {
        return [
            'name' . $languagePostFix => [
                'required' => true,
                'validators' => [
                    [
                        'name' => 'StringLength',
                        'options' => [
                            'encoding' => 'UTF-8',
                            'min' => 1,
                            'max' => 100,
                        ],
                    ],
                ],
            ],
            'location' . $languagePostFix => [
                'required' => true,
                'validators' => [
                    [
                        'name' => 'StringLength',
                        'options' => [
                            'encoding' => 'UTF-8',
                            'min' => 1,
                            'max' => 100,
                        ],
                    ],
                ],
            ],
            'costs' . $languagePostFix => [
                'required' => true,
                'validators' => [
                    [
                        'name' => 'StringLength',
                        'options' => [
                            'encoding' => 'UTF-8',
                            'min' => 1,
                            'max' => 100,
                        ],
                    ],
                ],
            ],
            'description' . $languagePostFix => [
                'required' => true,
                'validators' => [
                    [
                        'name' => 'StringLength',
                        'options' => [
                            'encoding' => 'UTF-8',
                            'min' => 1,
                            'max' => 100000,
                        ],
                    ],
                ],
            ],
        ];
    }

    /***
     * Add  the input filter for the Dutch language
     *
     * @return array
     */
    public function inputFilterDutch()
    {
        return $this->inputFilterGeneric('');
    }
}
