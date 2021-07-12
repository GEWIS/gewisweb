<?php

namespace Company\Form;

use Laminas\Form\Form;
use Laminas\InputFilter\InputFilter;
use Laminas\Mvc\I18n\Translator;

class EditPackage extends Form
{
    public function __construct(Translator $translate, $type)
    {
        // we want to ignore the name passed
        parent::__construct();

        $this->setAttribute('method', 'post');

        $this->add(
            [
            'name' => 'id',
            'type' => 'hidden',
            ]
        );

        $this->add(
            [
            'name' => 'startDate',
            'type' => 'Laminas\Form\Element\Date',
            'attributes' => [
                'required' => 'required',
                'step' => '1',
            ],
            'options' => [
                'label' => $translate->translate('Start date'),
            ],
            ]
        );

        $this->add(
            [
            'name' => 'expirationDate',
            'type' => 'Laminas\Form\Element\Date',
            'attributes' => [
                'required' => 'required',
                'step' => '1',
            ],
            'options' => [
                'label' => $translate->translate('Expiration date'),
            ],
            ]
        );

        $this->add(
            [
            'name' => 'published',
            'type' => 'Laminas\Form\Element\Checkbox',
            'attributes' => [
            ],
            'options' => [
                'label' => $translate->translate('Published'),
                'value_options' => [
                    '0' => 'Enabled',
                ],
            ],
            ]
        );

        if ($type === "featured") {
            $this->add(
                [
                'name' => 'article',
                'type' => 'Laminas\Form\Element\Textarea',
                'options' => [
                    'label' => $translate->translate('Article'),
                ],
                'attributes' => [
                    'type' => 'textarea',
                ],
                ]
            );

            $this->add(
                [
                'type' => 'Laminas\Form\Element\Radio',
                'name' => 'language',
                'options' => [
                    'label' => 'Language',
                    'value_options' => [
                        'nl' => $translate->translate('Dutch'),
                        'en' => $translate->translate('English'),
                    ],
                ],
                ]
            );
        }

        if ($type === "banner") {
            $this->add(
                [
                'name' => 'banner',
                'type' => '\Laminas\Form\Element\File',
                'attributes' => [
                    'type' => 'file',
                ],
                'options' => [
                    'label' => $translate->translate('Banner'),
                ],
                ]
            );
        }

        $this->add(
            [
            'name' => 'submit',
            'attributes' => [
                'type' => 'submit',
                'value' => $translate->translate('Submit changes'),
                'id' => 'submitbutton',
            ],
            ]
        );

        $this->initFilters();
    }

    protected function initFilters()
    {
        $filter = new InputFilter();

        $filter->add(
            [
            'name' => 'startDate',
            'required' => true,
            'validators' => [
                ['name' => 'date'],
            ],
            'filters' => [
                ['name' => 'StripTags'],
                ['name' => 'StringTrim'],
            ],
            ]
        );

        $filter->add(
            [
            'name' => 'expirationDate',
            'required' => true,
            'validators' => [
                ['name' => 'date'],
            ],
            'filters' => [
                ['name' => 'StripTags'],
                ['name' => 'StringTrim'],
            ],
            ]
        );

        $this->setInputFilter($filter);
    }
}
