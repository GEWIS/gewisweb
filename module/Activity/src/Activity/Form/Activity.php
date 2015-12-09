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

        $this->Add([
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


    /***
     * Add  the input filter for the English language
     *
     * @param InputFilter $startInputFilter Input filter that needs to be appended
     * @return InputFilter
     */
    public function addInputFilterEnglish(InputFilter $startInputFilter)
    {
        return $this->addInputFilterGeneric($startInputFilter, '_en');
    }

    /***
     * Add  the input filter for the Dutch language
     *
     * @param InputFilter $startInputFilter Input filter that needs to be appended
     * @return InputFilter
     */
    public function addInputFilterDutch(InputFilter $startInputFilter)
    {
        return $this->addInputFilterGeneric($startInputFilter, '');
    }



    /**
     * Build a generic input filter
     *
     * @input InputFilter $inputFilter Starting inputFilter to add more stuff to
     * @input string $languagePostFix Postfix that is used for language fields to indicate that a field belongs to that
     * language
     * @return InputFilterInterface
     */
    protected function addInputFilterGeneric($inputFilter, $languagePostFix)
    {
        $factory = new InputFactory();
        $inputFilter->add($factory->createInput([
            'name' => 'name'. $languagePostFix,
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
            'name' => 'location' . $languagePostFix,
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
            'name' => 'costs' . $languagePostFix,
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
            'name' => 'description' . $languagePostFix,
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


        return $inputFilter;
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
    public function getInputFilter()
    {
        if (!is_null($this->inputFilter)) {
            return $this->inputFilter;
        }

        $inputFilter = new InputFilter();
        $factory = new InputFactory();

        $inputFilter->add($factory->createInput([
            'name' => 'costs_unknown',
            'required' => true
        ]));

        $inputFilter->add($factory->createInput([
            'name' => 'canSignUp',
            'required' => true
        ]));

        $inputFilter->add($factory->createInput([
            'name' => 'beginTime',
            'required' => true
        ]));

        $inputFilter->add($factory->createInput([
            'name' => 'endTime',
            'required' => true,
        ]));

        if ($this->data['language_english']) {
            $this->addInputFilterEnglish($inputFilter);
        }


        if ($this->data['language_dutch']) {
            $this->addInputFilterDutch($inputFilter);
        }

        // One of the language_dutch or language_english needs to set. If not, display a message at both, indicating that
        // they need to be set

        if (!$this->data['language_dutch'] && !$this->data['language_english']) {
            unset($this->data['language_dutch'], $this->data['language_english']);
            $inputFilter->add($factory->createInput([
                'name' => 'language_dutch',
                'required' => true,
            ]));

            $inputFilter->add($factory->createInput([
                'name' => 'language_english',
                'required' => true,
            ]));

        }
        $this->inputFilter = $inputFilter;
        return $this->inputFilter;
    }
}
