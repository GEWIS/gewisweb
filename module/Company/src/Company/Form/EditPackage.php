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

        $this->initFilters($translate);
    }

    protected function initFilters($translate)
    {
        $filter = new InputFilter();

        $filter->add([
            'name' => 'startDate',
            'required' => true,
            'validators' => [
                ['name' => 'date'],
            ],
            'filters' => [
                ['name' => 'StripTags'],
                ['name' => 'StringTrim'],
            ],
        ]);

        $filter->add([
            'name' => 'expirationDate',
            'required' => true,
            'validators' => [
//                new \Zend\Validator\Callback([
//                    ['name' => 'date'],
//                    'callback' => [$this, 'expAfterStart'],
//                    'message' => $translate->translate('Expiration date should be after start date'),
//                ]),
            ],
            'filters' => [
                ['name' => 'StripTags'],
                ['name' => 'StringTrim'],
            ],
        ]);

        $this->setInputFilter($filter);
    }

    public function expAfterStart($expirationDate, $context)
    {
        $startDate = $context['startDate'];
        return $expirationDate > $startDate;
    }

    public function checkCredits($expirationDate, $context)
    {
        // The timespan should be in days, not seconds -> divide by 86400
        $timespan = (strtotime($expirationDate) - strtotime($context['startDate']))/86400;
        // TODO retrieve the amount of bannercredits the company has
        $credits = 5;
        return $timespan <= $credits;
    }
}
