<?php

namespace Company\Form;

use Zend\Form\Form;
use Zend\InputFilter\InputFilter;
use Zend\Mvc\I18n\Translator as Translator;

class EditJob extends Form
{
    public function __construct($mapper, Translator $translate)
    {
        // we want to ignore the name passed
        parent::__construct();

        $this->mapper = $mapper;

        $this->setAttribute('method', 'post');

        $this->add([
            'name' => 'id',
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
            'name' => 'attachment_file',
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

        $this->initFilters($translate);
    }

    protected function initFilters($translate)
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
            'name' => 'slugName',
            'required' => true,
            'validators' => [
                new \Zend\Validator\Callback([
                    'callback' => [$this,'slugNameUnique'],
                    'message' => $translate->translate('This slug is already taken'),
                ]),
                new \Zend\Validator\Regex([
                    'message' => $translate->translate('This slug contains invalid characters') ,
                    'pattern' => '/^[0-9a-zA-Z_\-\.]*$/',
                ]),
            ],
            'filters' => [
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
            'name' => 'attachment_file',
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

        $this->setInputFilter($filter);
    }

    private $companySlug;

    public function setCompanySlug($companySlug)
    {
        $this->companySlug = $companySlug;
    }

    /**
     *
     * Checks if a given slugName is unique. (Callback for validation).
     *
     */
    public function slugNameUnique($slugName, $context)
    {
        $job = $this->getObject();
        $jid = $job->getId();
        return $this->mapper->isSlugNameUnique($this->companySlug, $slugName, $jid);

    }
}
