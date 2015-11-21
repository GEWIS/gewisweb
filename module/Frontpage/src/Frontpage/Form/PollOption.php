<?php

namespace Frontpage\Form;

use Frontpage\Model\PollOption as PollOptionModel;
use Zend\Form\Fieldset;
use Zend\Stdlib\Hydrator\ClassMethods as ClassMethodsHydrator;
use Zend\InputFilter\InputFilterProviderInterface;
use Zend\I18n\Translator\TranslatorInterface as Translator;

class PollOption extends Fieldset implements InputFilterProviderInterface
{
    public function __construct()
    {

        parent::__construct('pollOption');

        $this->setHydrator(new ClassMethodsHydrator(false))
            ->setObject(new PollOptionModel());

        $this->add(array(
            'name' => 'dutchText',
            'type' => 'text',
            'options' => array(
                'label' => 'Dutch option'
            )
        ));

        $this->add(array(
            'name' => 'englishText',
            'type' => 'text',
            'options' => array(
                'label' => 'English option'
            )
        ));
    }

    /**
     * @return array
     */
    public function getInputFilterSpecification()
    {
        return array(
            'dutchText' => array(
                'required' => true,
                'validators' => array(
                    array(
                        'name' => 'string_length',
                        'options' => array(
                            'min' => 3,
                            'max' => 75
                        )
                    ),
                ),
            ),

            'englishText' => array(
                'required' => true,
                'validators' => array(
                    array(
                        'name' => 'string_length',
                        'options' => array(
                            'min' => 3,
                            'max' => 75
                        )
                    ),
                ),
            ),
        );
    }
}