<?php

namespace Activity\Form;

use Zend\Form\Form;
//input filter
use Zend\InputFilter\InputFilterInterface;
use Zend\Stdlib\Hydrator\ClassMethods as ClassMethodsHydrator;
use Zend\InputFilter\InputFilterProviderInterface;

class Activity extends Form implements InputFilterProviderInterface
{
    /**
     * @var InputFilter
     */
    protected $inputFilter;
    protected $organs;

    public function __construct()
    {
        parent::__construct('activity');
        $this->setAttribute('method', 'post');
        $this->setHydrator(new ClassMethodsHydrator(false))
            ->setObject(new \Activity\Model\Activity());

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
                'value_options' => [
                    'a',
                    'b',
                    'c',
                ]
            ]
        ]);

        $this->add([
           // 'type' => 'Zend\Form\Element\DateTime',
            'name' => 'beginTime',
            'attributes' => [
                'type' => 'text',
           //     'min' => '2010-01-01T00:00:00Z',
           //     'step' => '1', // minutes; default step interval is 1 min
           //     'style' => 'width:100%',
            ],
        ]);

        $this->add([
          //  'type' => 'Zend\Form\Element\DateTime',
            'name' => 'endTime',
            'attributes' => [
                'type' => 'text',
            //    'min' => '2010-01-01T00:00:00Z',
              //  'step' => '1', // minutes; default step interval is 1 min
               // 'style' => 'width:100%',
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

        /*$this->add([
            'name' => 'approved',
            'type' => 'Zend\Form\Element\Checkbox',
            'options' => [
                'use_hidden_element' => true,
                'checked_value' => 1,
                'unchecked_value' => 0,
            ],
        ]);*/
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
            'options' => array(
                'count' => 0,
                'should_create_template' => true,
                'allow_add' => true,
                'target_element' => [
                    'type' => 'Activity\Form\ActivityFieldFieldset'
                ]
            )
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
            ]
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
