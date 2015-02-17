<?php
namespace Activity\Form;

use Zend\Form\Form;

//input filter
use Zend\InputFilter\Factory as InputFactory;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterAwareInterface;
use Zend\InputFilter\InputFilterInterface;

class Activity extends Form
{
    protected $inputFilter;
    public function __construct() {
        parent::__construct('activity');
        $this->setAttribute('method', 'post');

        $this->add([
            'name' => 'name',
            'attributes' => [
                'type' => 'text',
				'style' => 'width:100%'
            ]
        ]);

        $this->add([
            'type' => 'Zend\Form\Element\DateTime',
            'name' => 'beginTime',
            'attributes' => [
                'min' => '2010-01-01T00:00:00Z',
                'step' => '1', // minutes; default step interval is 1 min
				'style' => 'width:100%'
            ]
        ]);

        $this->add([
            'type' => 'Zend\Form\Element\DateTime',
            'name' => 'endTime',
            'attributes' => [
                'min' => '2010-01-01T00:00:00Z',
                'step' => '1', // minutes; default step interval is 1 min
				'style' => 'width:100%'
            ]
        ]);

        $this->add([
            'name' => 'location',
            'attributes' => [
                'type' => 'text',
				'style' => 'width:100%'
            ]
        ]);

        $this->add([
            'name' => 'costs',
            'attributes' => [
                'type' => 'text',
				'style' => 'width:100%'
            ]
        ]);
		$this->add([
			'name' => 'optie',			
			'type' => 'Zend\Form\Element\Checkbox',
			'options' => array(
				'use_hidden_element' => true,
				'checked_value' => 1,
				'unchecked_value' => 0
			)
		]);
		$this->add([
			'name' => 'approved',			
			'type' => 'Zend\Form\Element\Checkbox',
			'options' => array(
				'use_hidden_element' => true,
				'checked_value' => 1,
				'unchecked_value' => 0
			)
		]);
		$this->add([
			'name' => 'discription',
            'attributes' => [
                'type' => 'textarea',
				'style' => 'width:100%; height:10em; resize:none'
            ]
		]);
        $this->add(array(
            'name' => 'submit',
            'attributes' => array(
                'type'  => 'submit',
                'value' => 'Create',
            ),
        ));
    }

    /*************** INPUT FILTER*****************/
    /** The code below this deals with the input filter
     * of the create and edit activity form data
     */

    /**
     * Get the input filter
     *
     * @return InputFilterInterface
     */
    public function getInputFilter() {
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
            'required'=> true
        ]));

        $inputFilter->add($factory->createInput([
            'name' => 'name',
            'required' => true,
            'filters' => [
                ['name' => 'StripTags'],
                ['name' => 'StringTrim']
            ],
            'validators' => [
                [
                    'name'    => 'StringLength',
                    'options' => [
                        'encoding' => 'UTF-8',
                        'min'      => 1,
                        'max'      => 100,
                    ],
                ],
            ],
        ]));

        $inputFilter->add($factory->createInput([
            'name' => 'location',
            'required' => true,
            'filters' => [
                ['name' => 'StripTags'],
                ['name' => 'StringTrim']
            ],
            'validators' => [
                [
                    'name'    => 'StringLength',
                    'options' => [
                        'encoding' => 'UTF-8',
                        'min'      => 1,
                        'max'      => 100,
                    ],
                ],
            ],
        ]));

        $inputFilter->add($factory->createInput([
            'name' => 'costs',
            'required' => true,
            'filters' => [
                ['name' => 'Int'],
            ],
            'validators' => [
                [
                    'name'    => 'Between',
                    'options' => [
                        'min'      => 0,
                        'max'      => 10000,
                    ],
                ],
            ],
        ]));
		
		$inputFilter->add($factory->createInput([
            'name' => 'discription',
            'required' => true,
            'filters' => [
                ['name' => 'StripTags'],
                ['name' => 'StringTrim']
            ],
            'validators' => [
                [
                    'name'    => 'StringLength',
                    'options' => [
                        'encoding' => 'UTF-8',
                        'min'      => 1,
                        'max'      => 100000,
                    ],
                ],
            ],
        ]));
		$inputFilter->add($factory->createInput([
            'name' => 'optie',
            'required' => true,
            'validators' => [
                [
                    'name'    => 'inArray',
                    'options' => [
                        'haystack' => array(2,3),
						'strict'   => 'COMPARE_STRICT'
                    ],
                ],
            ],
        ]));

        $this->inputFilter = $inputFilter;
        return $this->inputFilter;
    }

    public function setInputFilter(InputFilterInterface $inputFilter) {
        throw new \Exception("Not used");
    }
}