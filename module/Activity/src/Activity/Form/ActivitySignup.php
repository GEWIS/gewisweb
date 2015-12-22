<?php

namespace Activity\Form;

use Zend\Form\Form;
//input filter
use Zend\InputFilter\InputFilterInterface;
use Zend\Stdlib\Hydrator\ClassMethods as ClassMethodsHydrator;
use Zend\InputFilter\InputFilterProviderInterface;

class ActivitySignup extends Form implements InputFilterProviderInterface
{

    public function __construct()
    {
        parent::__construct('activitysignup');
        $this->setAttribute('method', 'post');
        $this->setHydrator(new ClassMethodsHydrator(false))
            ->setObject(new \Activity\Model\ActivitySignup());

        $this->add([
            'name' => 'submit',
            'attributes' => [
                'type' => 'submit',
                'value' => 'Subscribe',
            ],
        ]);
    }

    /**
     * Initilialise the form, i.e. set the language and the fields
     * Add every field in $fields to the form.
     * 
     * @param ActivityField $fields
     */
    public function initialiseForm($fields, $setEnglish)
    {
            foreach($fields as $field){
                $this->add($this->createFieldElementArray($field, $setEnglish));
            }
    }

    /**
     * Apparently, validators are automatically added, so this works.
     *
     * @return type array
     */
    public function getInputFilterSpecification()
    {
        return [];
    }

    public function setInputFilter(InputFilterInterface $inputFilter)
    {
        throw new \Exception('Not used');
    }

    /**
     * Creates an array of the form element specification for the given $field,
     * to be used by the factory.
     *
     * @param \Activity\Model\ActivityField $field
     * @param bool $setEnglish
     * @return array
     */
    protected function createFieldElementArray(\Activity\Model\ActivityField $field, $setEnglish){

        $result = [
            'name' => $field->getId(),
        ];
        switch($field->getType()){
            case 0: //'Text'
                $result['type'] = 'Text';
                break;
            case 1: //'Yes/No'
                $result['type'] = 'Zend\Form\Element\Radio';
                $result['options'] = [
                    'value_options' => [
                        '1' => 'Yes',
                        '0' => 'No',
                    ]
                ];
                break;
            case 2: //'Number'
                $result['type'] = 'Zend\Form\Element\Number';
                $result['attributes'] = [
                    'min' => $field->getMinimumValue(),
                    'max' => $field->getMaximumValue(),
                    'step' => '1'
                ];
                break;
            case 3: //'Choice'
                $values = [];
                foreach($field->getOptions() as $option){
                    $values[$option->getId()] = 
                            $setEnglish ? $option->getValueEn() : $option->getValue();
                }
                $result['type'] = 'Zend\Form\Element\Select';
                $result['options'] = [
                    //'empty_option' => 'Make a choice',
                    'value_options' => $values
                ];
                break;
        }
        return $result;
    }
}
