<?php

namespace Activity\Form;

use Zend\Form\Form;
//input filter
use Zend\InputFilter\Factory as InputFactory;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterInterface;

class Activity extends Form
{
    protected $inputFilter;
    protected $organs;
    public function __construct()
    {
        parent::__construct('activity');
        $this->setAttribute('method', 'post');

        $this->add([
            'name' => 'name',
            'attributes' => [
                'type' => 'text',
                'style' => 'width:100%',
            ],
        ]);

        $this->add([
            'name' => 'name_en',
            'attributes' => [
                'type' => 'text',
                'style' => 'width:100%',
            ],
        ]);

        $this->add([
            'type' => 'Zend\Form\Element\DateTime',
            'name' => 'beginTime',
            'attributes' => [
                'min' => '2010-01-01T00:00:00Z',
                'step' => '1', // minutes; default step interval is 1 min
                'style' => 'width:100%',
            ],
        ]);

        $this->add([
            'type' => 'Zend\Form\Element\DateTime',
            'name' => 'endTime',
            'attributes' => [
                'min' => '2010-01-01T00:00:00Z',
                'step' => '1', // minutes; default step interval is 1 min
                'style' => 'width:100%',
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
            'name' => 'location_en',
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
            'name' => 'costs_en',
            'attributes' => [
                'type' => 'text',
                'style' => 'width:100%',
            ],
        ]);

        $this->add([
            'name' => 'costs_unknown',
            'type' => 'Zend\Form\Element\Checkbox',
            'options' => [
                'use_hidden_element' => true,
                'checked_value' => 1,
                'unchecked_value' => 0,
            ],
        ]);

        $this->add([
            'name' => 'approved',
            'type' => 'Zend\Form\Element\Checkbox',
            'options' => [
                'use_hidden_element' => true,
                'checked_value' => 1,
                'unchecked_value' => 0,
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
            'name' => 'description_en',
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
            'name' => 'submit',
            'attributes' => [
                'type' => 'submit',
                'value' => 'Create',
            ],
        ]);
    }

    /*************** INPUT FILTER*****************/
    /** The code below this deals with the input filter
     * of the create and edit activity form data.
     */

    /**
     * Get the input filter.
     *
     * @return InputFilterInterface
     */
    public function getInputFilter()
    {
        // Check if the input filter is set. If so, serve
        if ($this->inputFilter) {
            return $this->inputFilter;
        }

        $inputFilter = new InputFilter();
        $factory = new InputFactory();

        $inputFilter->add($factory->createInput([
            'name' => 'beginTime',
            'required' => true,
        ]));

        $inputFilter->add($factory->createInput([
            'name' => 'endTime',
            'required' => true,
        ]));
        $inputFilter->add($factory->createInput([
            'name' => 'name',
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
        ]));

        $inputFilter->add($factory->createInput([
            'name' => 'name_en',
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
        ]));

        $inputFilter->add($factory->createInput([
            'name' => 'location',
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
        ]));

        $inputFilter->add($factory->createInput([
            'name' => 'location_en',
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
        ]));

        $inputFilter->add($factory->createInput([
            'name' => 'costs',
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
        ]));

        $inputFilter->add($factory->createInput([
            'name' => 'costs_en',
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
        ]));

        $inputFilter->add($factory->createInput([
            'name' => 'description',
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
        ]));

        $inputFilter->add($factory->createInput([
            'name' => 'description_en',
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
        ]));

        $inputFilter->add($factory->createInput([
            'name' => 'costs_unknown',
            'required' => true
        ]));

        $inputFilter->add($factory->createInput([
            'name' => 'canSignUp',
            'required' => true
        ]));

        $this->inputFilter = $inputFilter;

        return $this->inputFilter;
    }

    public function setInputFilter(InputFilterInterface $inputFilter)
    {
        throw new \Exception('Not used');
    }
}
