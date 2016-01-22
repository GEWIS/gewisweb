<?php

namespace Activity\Form;

use Decision\Model\Organ;
use Zend\Form\Form;
use Zend\Mvc\I18n\Translator;
use Zend\InputFilter\InputFilterInterface;
use Zend\Stdlib\Hydrator\ClassMethods as ClassMethodsHydrator;
use Zend\InputFilter\InputFilterProviderInterface;
use Zend\Validator\NotEmpty;

class Activity extends Form implements InputFilterProviderInterface
{
    /**
     * @var InputFilter
     */
    protected $inputFilter;
    protected $organs;

    /**
     * @param Organ[] $organs
     * @param Translator $translator
     */
    public function __construct(array $organs, Translator $translator)
    {
        parent::__construct('activity');
        $this->setAttribute('method', 'post');
        $this->setHydrator(new ClassMethodsHydrator(false))
            ->setObject(new \Activity\Model\Activity());

        // all the organs that the user belongs to in organId => name pairs
        $organOptions = [0 => $translator->translate('No organ')];

        foreach ($organs as $organ) {
            $organOptions[$organ->getId()] = $organ->getName();
        }

        // Find user that wants to create an activity

        $this->add([
            'name' => 'language_dutch',
            'type' => 'checkbox',
            'uncheckedValue' => null,
        ]);

        $this->add([
            'name' => 'language_english',
            'type' => 'checkbox',
            'uncheckedValue' => null,
        ]);

        $this->add([
            'name' => 'name',
            'attributes' => [
                'type' => 'text',
                'style' => 'width:100%',
            ],
        ]);

        $this->add([
            'name' => 'nameEn',
            'attributes' => [
                'type' => 'text',
                'style' => 'width:100%',
            ],
        ]);

        $this->add([
            'name' => 'organ',
            'type' => 'select',
            'options' => [
                'style' => 'width:100%',
                'value_options' => $organOptions
            ]
        ]);

        $this->add([
            'name' => 'beginTime',
            'attributes' => [
                'type' => 'text',
            ],
        ]);

        $this->add([
            'name' => 'endTime',
            'attributes' => [
                'type' => 'text',
            ],
        ]);

        $this->add([
            'name' => 'location',
            'attributes' => [
                'type' => 'text',
                'style' => 'width:100%',
            ],
        ]);

        $this->add([
            'name' => 'locationEn',
            'attributes' => [
                'type' => 'text',
                'style' => 'width:100%',
            ],
        ]);

        $this->add([
            'name' => 'costs',
            'attributes' => [
                'type' => 'text',
                'style' => 'width:100%',
            ],
        ]);
        $this->add([
            'name' => 'costsEn',
            'attributes' => [
                'type' => 'text',
                'style' => 'width:100%',
            ],
        ]);

        $this->add([
            'name' => 'description',
            'attributes' => [
                'type' => 'textarea',
                'style' => 'width:100%; height:10em; resize:none',
            ],
        ]);

        $this->add([
            'name' => 'descriptionEn',
            'attributes' => [
                'type' => 'textarea',
                'style' => 'width:100%; height:10em; resize:none',
            ],
        ]);

        $this->add([
            'name' => 'canSignUp',
            'type' => 'Zend\Form\Element\Checkbox',
            'options' => [
                'checked_value' => 1,
                'unchecked_value' => 0,
            ],
        ]);

        $this->add([
            'name' => 'fields',
            'type' => 'Zend\Form\Element\Collection',
            'options' => [
                'count' => 0,
                'should_create_template' => true,
                'allow_add' => true,
                'target_element' => new ActivityFieldFieldset()
            ]
        ]);

        $this->add([
            'name' => 'subscriptionDeadline',
            'attributes' => [
                'type' => 'text'
            ]
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
         */
        foreach ($this->get('fields')->getFieldSets() as $fieldset)
        {
            if ($this->data['language_english']) {
                if (!(new NotEmpty())->isValid($fieldset->get('nameEn')->getValue())) {
                    //TODO: Return error messages
                    $valid = false;
                }

                if ($fieldset->get('type')->getValue() === '3' && !(new NotEmpty())->isValid($fieldset->get('optionsEn')->getValue())) {
                    //TODO: Return error messages
                    $valid = false;
                }
            }


            if ($this->data['language_dutch']) {
                if (!(new NotEmpty())->isValid($fieldset->get('name')->getValue())) {
                    //TODO: Return error messages
                    $valid = false;
                }

                if ($fieldset->get('type')->getValue() === '3' && !(new NotEmpty())->isValid($fieldset->get('options')->getValue())) {
                    //TODO: Return error messages
                    $valid = false;
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
            'location'. $languagePostFix => [
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
            'costs'. $languagePostFix => [
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
            'description'. $languagePostFix => [
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

    public function setInputFilter(InputFilterInterface $inputFilter)
    {
        throw new \Exception('Not used');
    }

    /**
     * Get the input filter. Will generate a different inputfilter depending on if the Dutch and/or English language
     * is set
     * @return InputFilter0
     */
    public function getInputFilterSpecification()
    {
        $filter = [
            'beginTime' => [
                'required' => true,
            ],
            'endTime' => [
                'required' => true,
            ],

            'canSignUp' => [
                'required' => true
            ],
            'subscriptionDeadline' => [
                'required' => true
            ],
            'organ' => [
                'required' => true
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
}
