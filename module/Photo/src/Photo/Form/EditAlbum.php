<?php

namespace Photo\Form;

use Zend\Form\Form;
use Zend\InputFilter\InputFilter;
use Zend\MVc\I18n\Translator;

class EditAlbum extends Form
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
            'name' => 'startDateTime',
            'type' => 'Zend\Form\Element\DateTime',
            'options' => array(
                'label' => $translate->translate('Start date')
            )
        ));

        $this->add(array(
            'name' => 'endDateTime',
            'type' => 'Zend\Form\Element\DateTime',
            'options' => array(
                'label' => $translate->translate('End date')
            )
        ));

        $this->add(array(
            'name' => 'submit',
            'type' => 'submit',
            'options' => array(
                'label' => $translate->translate('Save')
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
                array('name' => 'not_empty'),
                array('name' => 'alnum',
                    'options' => array(
                        'allowWhiteSpace' => true
                    )
                )
            )
        ));
        
        $this->setInputFilter($filter);
    }

}
