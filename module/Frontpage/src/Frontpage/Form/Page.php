<?php

namespace Frontpage\Form;

use Zend\Form\Form;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterProviderInterface;
use Zend\I18n\Translator\TranslatorInterface as Translator;

class Page extends Form implements InputFilterProviderInterface
{

    public function __construct(Translator $translator)
    {
        parent::__construct();

        $this->add(array(
            'name' => 'category',
            'type' => 'text',
        ));

        $this->add(array(
            'name' => 'subCategory',
            'type' => 'text',
        ));

        $this->add(array(
            'name' => 'name',
            'type' => 'text',
        ));

        $this->add(array(
            'name' => 'dutchTitle',
            'type' => 'text',
            'options' => array(
                'label' => $translator->translate('Dutch title')
            )
        ));

        $this->add(array(
            'name' => 'englishTitle',
            'type' => 'text',
            'options' => array(
                'label' => $translator->translate('English title')
            )
        ));

        $this->add(array(
            'name' => 'dutchContent',
            'type' => 'text',
            'options' => array(
                'label' => $translator->translate('Dutch content')
            )
        ));

        $this->add(array(
            'name' => 'englishContent',
            'type' => 'text',
            'options' => array(
                'label' => $translator->translate('English content')
            )
        ));

        $this->add(array(
            'name' => 'requiredRole',
            'type' => 'text',
            'options' => array(
                'label' => $translator->translate('Required role'),
                'value' => 'guest'
            )
        ));

        $this->add(array(
            'name' => 'submit',
            'type' => 'submit',
            'attributes' => array(
                'value' => $translator->translate('Save')
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
            'category' => array(
                'required' => true,
                'validators' => array(
                    array(
                        'name' => 'string_length',
                        'options' => array(
                            'min' => 3,
                            'max' => 25
                        )
                    ),
                ),
                'filters' => array(
                    array('name' => 'string_to_lower')
                )
            ),

            'subCategory' => array(
                'required' => false,
                'validators' => array(
                    array(
                        'name' => 'string_length',
                        'options' => array(
                            'min' => 2,
                            'max' => 25
                        )
                    ),
                ),
                'filters' => array(
                    array('name' => 'string_to_lower'),
                    array('name' => 'to_null')
                )
            ),

            'name' => array(
                'required' => false,
                'validators' => array(
                    array(
                        'name' => 'string_length',
                        'options' => array(
                            'min' => 2,
                            'max' => 25
                        )
                    ),
                ),
                'filters' => array(
                    array('name' => 'string_to_lower'),
                    array('name' => 'to_null')
                )
            ),

            'dutchTitle' => array(
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

            'englishTitle' => array(
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

            'dutchContent' => array(
                'required' => true,
            ),

            'englishContent' => array(
                'required' => true,
            ),

            'requiredRole' => array(
                'required' => true,
            ),
        );
    }
}
