<?php

namespace Company\Form;

use Zend\Form\Form;
use Zend\InputFilter\InputFilter;
use Zend\Mvc\I18n\Translator as Translator;

class EditJob extends Form
{
    public function __construct(Translator $translate)
    {
        // we want to ignore the name passed
        parent::__construct();

        $this->setAttribute('method', 'post');
        $this->add([
            'name' => 'job-id',
            'attributes' => [
                'type' => 'hidden',
            ],
        ]);
        $this->add([
            'name' => 'company-id',
            'attributes' => [
                'type' => 'hidden',
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
        $this->add([
            'name' => 'name',
            'attributes' => [
                'type' => 'text',
            ],
            'options' => [
                'label' => $translate->translate('Name'),
            ],
        ]);
        $this->add([
            'name' => 'slugName',
            'attributes' => [
                'type' => 'text',
            ],
            'options' => [
                'label' => $translate->translate('Permalink'),
            ],
        ]);
        $this->add([
            'name' => 'active',
            'type' => 'Zend\Form\Element\Checkbox',
            'options' => [
                'label' => $translate->translate('Active'),
                'use_hidden_element' => true,
                'checked_value' => '1',
                'unchecked_value' => '0',
            ],
        ]);
        $this->add([
            'name' => 'website',
            'attributes' => [
                'type' => 'text',
            ],
            'options' => [
                'label' => $translate->translate('Website'),
            ],
        ]);
        $this->add([
            'name' => 'attachment',
            'type' => '\Zend\Form\Element\File',
            'attributes' => [
                'type' => 'file',
            ],
            'options' => [
                'label' => $translate->translate('Attachment'),
            ],
        ]);
        $this->add([
            'name' => 'email',
            'type' => 'Zend\Form\Element\Email',
            'attributes' => [
                'type' => 'text',
            ],
            'options' => [
                'label' => $translate->translate('Email'),
            ],
        ]);
        $this->add([
            'name' => 'phone',
            'attributes' => [
                'type' => 'text',
            ],
            'options' => [
                'label' => $translate->translate('Phone'),
            ],
        ]);
        $this->add([
            'name' => 'description',
            'type' => 'Zend\Form\Element\Textarea',
            'options' => [
                'label' => $translate->translate('Description'),
            ],
        ]);
        $this->add([
            'name' => 'active',
            'type' => 'Zend\Form\Element\Checkbox',
            'options' => [
                'label' => $translate->translate('Active'),
            ],
        ]);
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

        $filter->add([
            'name' => 'name',
            'required' => true,
            'validators' => [
                [
                    'name' => 'string_length',
                    'options' => [
                        'min' => 2,
                        'max' => 127,
                    ],
                ],
            ],
        ]);

        $filter->add([
            'name' => 'website',
            'required' => false,
            'validators' => [
                [
                    'name' => 'uri',
                ],
            ],
        ]);

        $filter->add([
            'name' => 'description',
            'required' => false,
            'validators' => [
                [
                    'name' => 'string_length',
                    'options' => [
                        'min' => 2,
                        'max' => 10000,
                    ],
                ],
            ],
        ]);

        $filter->add([
            'name' => 'email',
            'required' => false,
            'validators' => [
                ['name' => 'email_address'],
            ],
        ]);

        $filter->add([
            'name' => 'attachment',
            'required' => false,
            'validators' => [
                [
                    'name' => 'File\Extension',
                    'options' => [
                        'extension' => 'pdf',
                    ],
                ],
                [
                    'name' => 'File\MimeType',
                    'options' => [
                        'mimeType' => 'application/pdf',
                    ],
                ],
            ],
        ]);

        // Cannot upload logo yet
        /*$filter->add(array(
            'name' => 'logo',
            'required' => false,
            'validators' => array(
                array(
                    'name' => 'File\Extension',
                    'options' => array(
                        'extension' => 'png'
                    )
                ),
                array(
                    'name' => 'File\MimeType',
                    'options' => array(
                        'mimeType' => 'image/png'
                    )
                )
            )
        ));*/

        /*
         * TODO: Add more filters
         *
         * Email filter: http://stackoverflow.com/questions/20946210/zend2-limiting-e-mail-validation-to-only-one-error-message
         */

        $this->setInputFilter($filter);
    }
}
