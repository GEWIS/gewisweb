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

        $this->add([
            'name' => 'name',
            'type' => 'Zend\Form\Element\Text',
            'options' => [
                'label' => $translate->translate('Album title')
            ]
        ]);

        $this->add([
            'name' => 'startDateTime',
            'type' => 'Zend\Form\Element\DateTime',
            'options' => [
                'label' => $translate->translate('Start date')
            ]
        ]);

        $this->add([
            'name' => 'endDateTime',
            'type' => 'Zend\Form\Element\DateTime',
            'options' => [
                'label' => $translate->translate('End date')
            ]
        ]);

        $this->add([
            'name' => 'submit',
            'type' => 'submit',
            'options' => [
                'label' => $translate->translate('Save')
            ]
        ]);

        $this->initFilters();
    }

    protected function initFilters()
    {
        $filter = new InputFilter();

        $filter->add([
            'name' => 'name',
            'required' => true,
            'validators' => [
                ['name' => 'not_empty'],
                [
                    'name' => 'alnum',
                    'options' => [
                        'allowWhiteSpace' => true
                    ]
                ]
            ]
        ]);

        $this->setInputFilter($filter);
    }

}
