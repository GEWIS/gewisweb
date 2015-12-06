<?php

namespace Activity\Form;

use Zend\Form\Form;
//input filter
use Zend\InputFilter\InputFilterInterface;
use Zend\Stdlib\Hydrator\ClassMethods as ClassMethodsHydrator;
use Zend\InputFilter\InputFilterProviderInterface;

class Activity extends Form implements InputFilterProviderInterface
{
    protected $inputFilter;
    protected $organs;
    public function __construct()
    {
        parent::__construct('activity');
        $this->setAttribute('method', 'post');
        $this->setHydrator(new ClassMethodsHydrator(false))
            ->setObject(new \Activity\Model\Activity());

        $this->add([
            'name' => 'name',
            'attributes' => [
                'type' => 'text',
                'style' => 'width:100%',
            ],
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
            'name' => 'costs',
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
                'target_element' => array(
                    'type' => 'Activity\Form\ActivityFieldFieldset'
                )
            )
        ]);

        $this->add([
            'name' => 'submit',
            'attributes' => [
                'type' => 'submit',
                'value' => 'Create',
            ],
        ]);
    }


    public function getInputFilterSpecification()
    {
        return [
            'beginTime' => [
                'required' => true,
            ],
            'endTime' => [
                'required' => true,
            ],
            'name' => [
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
            'location' => [
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
            'description' => [
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
            'canSignUp' => [
                'required' => true
            ],
            'costs_unknown' => [
                'required' => true
            ],
        ];
    }
    
    public function setInputFilter(InputFilterInterface $inputFilter)
    {
        throw new \Exception('Not used');
    }
}
