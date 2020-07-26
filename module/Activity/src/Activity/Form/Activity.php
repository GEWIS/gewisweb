<?php

namespace Activity\Form;

use Decision\Model\Organ;
use Zend\Form\Form;
use Zend\Mvc\I18n\Translator;
use Doctrine\Common\Persistence\ObjectManager;
//use Zend\InputFilter\InputFilterInterface;
use Zend\Stdlib\Hydrator\ClassMethods as ClassMethodsHydrator;
use Zend\InputFilter\InputFilterProviderInterface;
use Zend\Validator\NotEmpty;

class Activity extends Form implements InputFilterProviderInterface
{
    protected $organs;
    protected $translator;

    /**
     * @param Organ[] $organs
     * @param Translator $translator
     */
    public function __construct(array $organs, Translator $translator, ObjectManager $objectManager)
    {
        parent::__construct('activity');
        $this->translator = $translator;

        $this->setAttribute('method', 'post');
        //$this->setHydrator(new ClassMethodsHydrator(false))
        //    ->setObject(new \Activity\Model\Activity());

        // all the organs that the user belongs to in organId => name pairs
        $organOptions = [0 => $translator->translate('No organ')];

        foreach ($organs as $organ) {
            $organOptions[$organ->getId()] = $organ->getAbbr();
        }

        $this->add([
            'name' => 'organ',
            'type' => 'select',
            'options' => [
                'value_options' => $organOptions
            ]
        ]);

        $this->add([
            'name' => 'beginTime',
            'type' => 'datetime',
            'options' => [
                'format' => 'Y/m/d H:i'
            ],
        ]);

        $this->add([
            'name' => 'endTime',
            'type' => 'datetime',
            'options' => [
                'format' => 'Y/m/d H:i'
            ],
        ]);

        $this->add([
            'name' => 'language_dutch',
            'type' => 'Zend\Form\Element\Checkbox',
            'options' => [
                'checked_value' => 1,
                'unchecked_value' => 0,
            ],
        ]);

        $this->add([
            'name' => 'language_english',
            'type' => 'Zend\Form\Element\Checkbox',
            'options' => [
                'checked_value' => 1,
                'unchecked_value' => 0,
            ],
        ]);

        $this->add([
            'name' => 'name',
            'attributes' => [
                'type' => 'text',
            ],
        ]);

        $this->add([
            'name' => 'nameEn',
            'attributes' => [
                'type' => 'text',
            ],
        ]);

        $this->add([
            'name' => 'location',
            'attributes' => [
                'type' => 'text',
            ],
        ]);

        $this->add([
            'name' => 'locationEn',
            'attributes' => [
                'type' => 'text',
            ],
        ]);

        $this->add([
            'name' => 'costs',
            'attributes' => [
                'type' => 'text',
            ],
        ]);
        $this->add([
            'name' => 'costsEn',
            'attributes' => [
                'type' => 'text',
            ],
        ]);

        $this->add([
            'name' => 'description',
            'attributes' => [
                'type' => 'textarea',
            ],
        ]);

        $this->add([
            'name' => 'descriptionEn',
            'attributes' => [
                'type' => 'textarea',
            ],
        ]);

        $this->add([
            'name' => 'isMyFuture',
            'type' => 'Zend\Form\Element\Checkbox',
            'options' => [
                'checked_value' => 1,
                'unchecked_value' => 0,
            ],
        ]);

        $this->add([
            'name' => 'requireGEFLITST',
            'type' => 'Zend\Form\Element\Checkbox',
            'options' => [
                'checked_value' => 1,
                'unchecked_value' => 0,
            ],
        ]);

        $this->add([
            'name' => 'signuplists',
            'type' => 'Zend\Form\Element\Collection',
            'options' => [
                'count' => 0,
                'should_create_template' => true,
                'template_placeholder' => '__signuplist__',
                'allow_add' => true,
                'target_element' => new SignupList($translator),
            ],
        ]);

        $this->add([
            'name' => 'submit',
            'attributes' => [
                'type' => 'submit',
                'value' => 'Create',
            ],
        ]);
    }

    /**
     * Validate the form
     *
     * @return bool
     * @throws Exception\DomainException
     */
    public function isValid()
    {
        $valid = parent::isValid();
        /*
         * This might seem like a bit of a hack, but this is probably the only way zend framework
         * allows us to do this.
         *
         * TODO: Move this to an actual InputFilter to add messages, because
         * marking inputs as invalid (and adding messages) cannot be done from
         * this function.
         */
        if (isset($this->data['language_dutch']) && isset($this->data['language_english'])) {
            foreach ($this->get('signuplists')->getFieldSets() as $keySignupList => $signupList) {
                if ($this->data['language_dutch']) {
                    if (!(new NotEmpty())->isValid($signupList->get('name')->getValue())) {
                        // TODO: Return error messages
                        $valid = false;
                    }
                }

                if ($this->data['language_english']) {
                    if (!(new NotEmpty())->isValid($signupList->get('nameEn')->getValue())) {
                        // TODO: Return error messages
                        $valid = false;
                    }
                }

                foreach ($signupList->get('fields')->getFieldSets() as $keyField => $field) {
                    if ($this->data['language_dutch']) {
                        if (!(new NotEmpty())->isValid($field->get('name')->getValue())) {
                            // TODO: Return error messages
                            $valid = false;
                        }

                        if ($field->get('type')->getValue() === '3' && !(new NotEmpty())->isValid($field->get('options')->getValue())) {
                            // TODO: Return error messages
                            $valid = false;
                        }
                    }

                    if ($this->data['language_english']) {
                        if (!(new NotEmpty())->isValid($field->get('nameEn')->getValue())) {
                            // TODO: Return error messages
                            $valid = false;
                        }

                        if ($field->get('type')->getValue() === '3' && !(new NotEmpty())->isValid($field->get('optionsEn')->getValue())) {
                            // TODO: Return error messages
                            $valid = false;
                        }
                    }
                }
            }
        }

        $this->isValid = $valid;

        return $valid;
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

    /***
     * Add  the input filter for the Dutch language
     *
     * @return array
     */
    public function inputFilterDutch()
    {
        return $this->inputFilterGeneric('');
    }

    /**
     * Build a generic input filter
     *
     * @input string $languagePostFix Postfix that is used for language fields to indicate that a field belongs to that
     * language
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

    /**
     * Get the input filter. Will generate a different inputfilter depending on if the Dutch and/or English language
     * is set
     * @return InputFilter
     */
    public function getInputFilterSpecification()
    {
        $filter = [
            'organ' => [
                'required' => true,
            ],
            'beginTime' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => 'callback',
                        'options' => [
                            'messages' => [
                                \Zend\Validator\Callback::INVALID_VALUE =>
                                    $this->translator->translate('The activity must start before it ends.'),
                            ],
                            'callback' => [$this, 'beforeEndTime']
                        ],
                    ],
                ],
            ],
            'endTime' => [
                'required' => true,
            ],
        ];

        if ($this->data['language_english']) {
            $filter += $this->inputFilterEnglish();
        }

        if ($this->data['language_dutch']) {
            $filter += $this->inputFilterDutch();
        }
        // One of the language_dutch or language_english needs to set. If not, display a message at both, indicating that
        // they need to be set

        if (!$this->data['language_dutch'] && !$this->data['language_english']) {
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

    /**
     * Check if a certain date is before the end date of the activity.
     *
     * @param $value
     * @param array $context
     * @return bool
     */
    public static function beforeEndTime($value, $context = [])
    {
        try {
            $thisTime = new \DateTime($value);
            $endTime = isset($context['endTime']) ? new \DateTime($context['endTime']) : new \DateTime('now');
            return $thisTime <= $endTime;
        } catch (\Exception $e) {
            // An exception is an indication that one of the times was not valid
            return false;
        }
    }

    /**
     * Checks if a certain date is before the begin date of the activity.
     *
     * @param $value
     * @param array $context
     * @return boolean
     */
    public static function beforeBeginTime($value, $context = []) {
        try {
            $thisTime = new \DateTime($value);
            $beginTime = isset($context['beginTime']) ? new \DateTime($context['beginTime']) : new \DateTime('now');
            return $thisTime <= $beginTime;
        } catch (\Exception $e) {
            // An exception is an indication that one of the DateTimes was not valid
            return false;
        }
    }
}
