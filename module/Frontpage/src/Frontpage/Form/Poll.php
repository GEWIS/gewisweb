<?php

namespace Frontpage\Form;

use Zend\Form\Form;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterProviderInterface;
use Zend\I18n\Translator\TranslatorInterface as Translator;

class Poll extends Form implements InputFilterProviderInterface
{

    public function __construct(Translator $translator)
    {
        parent::__construct();

        $this->add(array(
            'name' => 'dutchQuestion',
            'type' => 'text',
            'options' => array(
                'label' => $translator->translate('Dutch question')
            )
        ));

        $this->add(array(
            'name' => 'englishQuestion',
            'type' => 'text',
            'options' => array(
                'label' => $translator->translate('English question')
            )
        ));

        $this->add([
            'name' => 'options',
            'type' => 'Zend\Form\Element\Collection',
            'options' => array(
                'count' => 2,
                'should_create_template' => true,
                'allow_add' => true,
                'target_element' => array(
                    'type' => 'Frontpage\Form\PollOption'
                )
            )
        ]);

        $this->add(array(
            'name' => 'submit',
            'type' => 'submit',
            'attributes' => array(
                'value' => $translator->translate('Submit')
            )
        ));
    }

    /**
     * Should return an array specification compatible with
     * {@link Zend\InputFilter\Factory::createInputFilter()}.
     *
     * @return array
     */
    public function getInputFilterSpecification()
    {
        return array(
            'dutchQuestion' => array(
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

            'englishQuestion' => array(
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
