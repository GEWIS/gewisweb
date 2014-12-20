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
                'type' => 'text'
            ],
            'options' => [
                'label' =>  'Name:'
            ]
        ]);

        $this->add([
            'name' => 'beginTime',
            'attributes' => [
                'type' => 'text'
            ],
            'options' => [
                'label' =>  'Begin date and time of the activity: (yyyy-mm-dd hh:mm)'
            ]
        ]);

        $this->add([
            'name' => 'endTime',
            'attributes' => [
                'type' => 'text'
            ],
            'options' => [
                'label' =>  'End date and time of the activity: (yyyy-mm-dd hh:mm)'
            ]
        ]);

        $this->add([
            'name' => 'location',
            'attributes' => [
                'type' => 'text'
            ],
            'options' => [
                'label' =>  'Location:'
            ]
        ]);

        $this->add([
            'name' => 'costs',
            'attributes' => [
                'type' => 'text'
            ],
            'options' => [
                'label' =>  'Costs:'
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
    /*************** INPUT FILTEr*****************/
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
            'required' => true
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

        $this->inputFilter = $inputFilter;
        return $this->inputFilter;
    }

    public function setInputFilter(InputFilterInterface $inputFilter) {
        throw new \Exception("Not used");
    }
}