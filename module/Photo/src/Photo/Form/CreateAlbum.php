<?php

namespace Photo\Form;

use Zend\Form\Form;
use Zend\InputFilter\InputFilter;
use Zend\MVc\I18n\Translator;

class CreateAlbum extends Form
{

    public function __construct(Translator $translate)
    {
        parent::__construct();

        $this->add(array(
            'name' => 'name',
            'type' => 'Zend\Form\Element\Text',
            'options' => array(
                'label' => $translate->translate('Album title')
            )
        ));

        $this->add(array(
            'name' => 'submit',
            'type' => 'submit',
            'options' => array(
                'label' => $translate->translate('Create')
            )
        ));

        $this->initFilters();
    }

    protected function initFilters()
    {
        $filter = new InputFilter();

        $filter->add(array(
            'name' => 'name',
            'required' => true,
            'validators' => array(
                array(
                    'name' => 'string_length',
                    'options' => array(
                        'encoding' => 'UTF-8',
                        'min' => 3,
                        'max' => 75
                    )
                )
            )
        ));

        $this->setInputFilter($filter);
    }

}
