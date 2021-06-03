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
        $this->setAttribute('method', 'post');

        $this->add([
            'name' => 'id',
            'type' => 'hidden',
        ]);

        $this->add([
            'name' => 'contractNumber',
            'attributes' => [
                'type' => 'text',
            ],
            'options' => [
                'label' => $translate->translate('Contract Number'),
                'required' => false,
            ],
        ]);

        $this->add([
            'name' => 'startDate',
            'type' => 'Zend\Form\Element\Date',
            'attributes' => [
                'required' => 'required',
                'step' => '1',
                'min' => $this->setTomorrow(),
                'value' => $this->setTomorrow()
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
                'min' => $this->setTomorrow(),
                'value' => $this->setDayInterval(14)
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

    /**
     * Method that returns the date of tomorrow
     *
     * @return \DateTime
     */
    public function setTomorrow() {
        $today = date("Y-m-d");
        return date('Y-m-d', strtotime($today . ' +1 day'));
    }

    /**
     * Returns the date of tomorrow plus a certain number of days
     *
     * @param $interval the number of days to add to tomorrow
     * @return \DateTime
     */
    public function setDayInterval($interval) {
        $tomorrow = $this->setTomorrow();
        return date('Y-m-d', strtotime($tomorrow . ' +' . strval($interval) .' day'));
    }

}
