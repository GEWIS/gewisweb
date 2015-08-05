<?php

namespace Frontpage\Form;

use Zend\Form\Form;
use Zend\InputFilter\InputFilter;
use Zend\I18n\Translator\TranslatorInterface as Translator;

class Page extends Form
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
            'name' => 'submit',
            'type' => 'submit',
            'attributes' => array(
                'value' => $translator->translate('Save')
            )
        ));

        $this->initFilters();
    }

    protected function initFilters()
    {
        $filter = new InputFilter();

        $filter->add(array(
            'name' => 'category',
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
        ));

        $filter->add(array(
            'name' => 'subCategory',
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
        ));

        $filter->add(array(
            'name' => 'name',
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
        ));

        $filter->add(array(
            'name' => 'dutchTitle',
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
        ));

        $filter->add(array(
            'name' => 'englishTitle',
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
        ));

        $filter->add(array(
            'name' => 'dutchContent',
            'required' => true,
        ));

        $filter->add(array(
            'name' => 'englishContent',
            'required' => true,
        ));



        $this->setInputFilter($filter);
    }
}
