<?php

namespace Company\Form;

use Zend\Form\Form;
use Zend\InputFilter\InputFilter;
use Zend\Mvc\I18n\Translator;

class EditCompany extends Form
{
    public function __construct(Translator $translate)
    {
        // we want to ignore the name passed
        parent::__construct();

        $this->setAttribute('method', 'post');
        $this->add([
            'name' => 'id',
            'attributes' => [
                'type' => 'hidden',
            ],
        ]);
        $this->add([
            'name' => 'translations',
            'attributes' => [
                'type' => 'hidden',
            ],
        ]);
        $this->add([
            'name' => 'languageNeutralId',
            'attributes' => [
                'type' => 'hidden',
            ],
        ]);
        $this->add([
            'name' => 'slugName',
            'attributes' => [
                'type' => 'text',
                'required' => 'required',
            ],
            'options' => [
                'label' => $translate->translate('Permalink'),
                'required' => 'required',
            ],
        ]);
        $this->add([
            'name' => 'name',
            'attributes' => [
                'type' => 'text',
                'required' => 'required',
            ],
            'options' => [
                'label' => $translate->translate('Name'),
                'required' => 'required',
            ],
        ]);
        $this->add([
            'name' => 'languages',
            'type' => 'MultiCheckbox',
            'options' => [
                'label' => $translate->translate('Languages'),
                'value_options' => [
                    'en' => $translate->translate('English'),
                    'nl' => $translate->translate('Dutch'),
                ],
            ],
        ]);
        $this->add([
            'name' => 'address',
            'type' => 'Zend\Form\Element\Textarea',
            'attributes' => [
                'type' => 'textarea',
            ],
            'options' => [
                'label' => $translate->translate('Location'),
            ],
        ]);
        // English version
        $this->add([
            'name' => 'en_website',
            'type' => 'Zend\Form\Element\Url',
            'attributes' => [
            ],
            'options' => [
                'label' => $translate->translate('Website'),
            ],
        ]);
        // Dutch version
        $this->add([
            'name' => 'nl_website',
            'type' => 'Zend\Form\Element\Url',
            'attributes' => [
            ],
            'options' => [
                'label' => $translate->translate('Website'),
            ],
        ]);
        $this->add([
            'name' => 'en_slogan',
            'attributes' => [
                'type' => 'text',
            ],
            'options' => [
                'label' => $translate->translate('Slogan'),
            ],
        ]);
        $this->add([
            'name' => 'nl_slogan',
            'attributes' => [
                'type' => 'text',
            ],
            'options' => [
                'label' => $translate->translate('Slogan'),
            ],
        ]);
        $this->add([
            'name' => 'email',
            'type' => 'Zend\Form\Element\Email',
            'attributes' => [
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
            'name' => 'nl_logo',
            'type' => '\Zend\Form\Element\File',
            'attributes' => [
                'type' => 'file',
            ],
            'options' => [
                'label' => $translate->translate('Logo'),
            ],
        ]);
        $this->add([
            'name' => 'en_logo',
            'type' => '\Zend\Form\Element\File',
            'attributes' => [
                'type' => 'file',
            ],
            'options' => [
                'label' => $translate->translate('Logo'),
            ],
        ]);
        $this->add([
            'name' => 'en_description',
            'type' => 'Zend\Form\Element\Textarea',
            'options' => [
                'label' => $translate->translate('Description'),
            ],
            'attributes' => [
                'type' => 'textarea',
            ],
        ]);
        $this->add([
            'name' => 'nl_description',
            'type' => 'Zend\Form\Element\Textarea',
            'options' => [
                'label' => $translate->translate('Description'),
            ],
            'attributes' => [
                'type' => 'textarea',
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
            'filters' => [
                ['name' => 'StripTags'],
                ['name' => 'StringTrim'],
            ],
        ]);

        $filter->add(array(
            'name' => 'en_website',
            'required' => false,
            'filters' => array(
                array('name' => 'StripTags'),
                array('name' => 'StringTrim')
            ),
            'validators' => array(
            )
        ));
        
        $filter->add(array(
            'name' => 'nl_website',
            'required' => false,
            'filters' => array(
                array('name' => 'StripTags'),
                array('name' => 'StringTrim')
            ),
            'validators' => array(
            )
        ));
        $filter->add(array(
            'name' => 'en_description',
            'required' => false,
            'validators' => array(
                array(
                    'name' => 'string_length',
                    'options' => array(
                        'min' => 2,
                        'max' => 10000
                    )
                )
            )
        ));
        $filter->add(array(
            'name' => 'nl_description',
            'required' => false,
            'validators' => array(
                array(
                    'name' => 'string_length',
                    'options' => array(
                        'min' => 2,
                        'max' => 10000
                    )
                )
            )
        ));

        $filter->add([
            'name' => 'email',
            'validators' => [
                [
                    'name' => 'EmailAddress',
                    'options' => [
                        'messages' => [
                            'emailAddressInvalidFormat' => 'Email address format is not valid',
                        ],
                    ],
                ],
            ],
            'filters' => [
                ['name' => 'StripTags'],
                ['name' => 'StringTrim'],
            ],
        ]);

        $filter->add([
            'name' => 'en_logo',
            'required' => false,
            'validators' => [
                [
                    'name' => 'File\Extension',
                    'options' => [
                        'extension' => 'png',
                    ],
                ],
                [
                    'name' => 'File\MimeType',
                    'options' => [
                        'mimeType' => 'image/png',
                    ],
                ],
            ],
        ]);
        $filter->add([
            'name' => 'nl_logo',
            'required' => false,
            'validators' => [
                [
                    'name' => 'File\Extension',
                    'options' => [
                        'extension' => 'png',
                    ],
                ],
                [
                    'name' => 'File\MimeType',
                    'options' => [
                        'mimeType' => 'image/png',
                    ],
                ],
            ],
        ]);

        $this->setInputFilter($filter);
    }
}
