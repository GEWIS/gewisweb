<?php

namespace Company\Form;

use Zend\Form\Form;
use Zend\InputFilter\InputFilter;
use Zend\Mvc\I18n\Translator;

class EditPackage extends Form
{
    public function __construct(Translator $translate, $type)
    {
        // we want to ignore the name passed
        parent::__construct();
        $today = date("Y-m-d");
        $this->setAttribute('method', 'post');

        $this->add([
            'name' => 'id',
            'type' => 'hidden',
        ]);

        $this->add([
            'name' => 'startDate',
            'type' => 'Zend\Form\Element\Date',
            'attributes' => [
                'required' => 'required',
                'step' => '1',
                'min' => $today
            ],
            'options' => [
                'label' => $translate->translate('Start date *'),
            ],
        ]);

        $this->add([
            'name' => 'expirationDate',
            'type' => 'Zend\Form\Element\Date',
            'attributes' => [
                'required' => 'required',
                'step' => '1',
                'min' => $today
            ],
            'options' => [
                'label' => $translate->translate('Expiration date *'),
            ],
        ]);

        $this->add([
            'name' => 'published',
            'type' => 'Zend\Form\Element\Checkbox',
            'attributes' => [
            ],
            'options' => [
                'label' => $translate->translate('Published'),
                'value_options' => [
                    '0' => 'Enabled',
                ],
            ],
        ]);

        if ($type === "featured") {
            $this->add([
                'name' => 'article',
                'type' => 'Zend\Form\Element\Textarea',
                'options' => [
                    'label' => $translate->translate('Article'),
                ],
                'attributes' => [
                    'type' => 'textarea',
                ],
            ]);

            $this->add([
                'type' => 'Zend\Form\Element\Radio',
                'name' => 'language',
                'options' => [
                    'label' => 'Language',
                    'value_options' => [
                        'nl' => $translate->translate('Dutch'),
                        'en' => $translate->translate('English'),
                    ],
                ],
            ]);
        }

        if ($type === "banner") {
            $this->add([
                'name' => 'banner',
                'required' => true,
                'type' => '\Zend\Form\Element\File',
                'attributes' => [
                    'type' => 'file',
                ],
                'options' => [
                    'label' => $translate->translate('Banner *'),
                ],
            ]);
        }

        $this->add([
            'name' => 'submit',
            'attributes' => [
                'type' => 'submit',
                'value' => $translate->translate('Submit changes'),
                'id' => 'submitbutton',
            ],
        ]);

        $this->initFilters();
    }

    protected function initFilters()
    {
        $filter = new InputFilter();


        $this->setInputFilter($filter);
    }

}
