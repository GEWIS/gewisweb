<?php

namespace Company\Form;

use Zend\Form\Form;
use Zend\InputFilter\InputFilter;
use Zend\I18n\Translator\TranslatorInterface as Translator;

class EditPackage extends Form
{
    const EXPIRATIONDATE_AFTER_STARTDATE = 'wrong_expirationDate';
    const START_DATE_IN_PAST = 'wrong_startDate';
    const INVALID_IMAGE_FILE = 'wrong_image';
    const IMAGE_WRONG_SIZE = 'wrong_imagesize';
    const NOT_ENOUGH_CREDITS_HIGHLIGHT = 'insufficient_credits_highlight';
    const NOT_ENOUGH_CREDITS_BANNER = 'insufficient_credits_banner';
    const COMPANY_HAS_THREE_HIGHLIGHTS = 'to_many_highlights';
    const ALREADY_THREE_HIGHLIGHTS_IN_CATEGORY = 'to_many_highlights_category';

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
                'value' => $this->setTomorrow()
            ],
            'options' => [
                'label' => $translate->translate("Start date *"),
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
                'label' => $translate->translate("Expiration date *"),
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
                    'label' => $translate->translate("Banner *"),
                ],
            ]);
        }

        if ($type === "highlight") {
            $this->add([
                'name' => 'vacancy_id',
                'required' => true,
                'type' => '\Zend\Form\Element\Select',
                'options' => [
                    'label' => $translate->translate("Select Vacancy *"),
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

    /**
     * Set the error.
     *
     * @param string $error
     */
    public function setError($error, Translator $translate)
    {
        switch ($error) {
            case self::EXPIRATIONDATE_AFTER_STARTDATE:
                $this->setMessages([
                    'expirationDate' => [
                       $translate->translate("Please make sure the expiration date is after the starting date.")
                    ]
                ]);
                break;
            case self::INVALID_IMAGE_FILE:
                $this->setMessages([
                    'banner' => [
                        $translate->translate("Please submit an image file.")
                    ]
                ]);
                break;
            case self::IMAGE_WRONG_SIZE:
                $this->setMessages([
                    'banner' => [
                        $translate->translate("The image you submitted does not have the right dimensions. The dimensions of the image should be 90 x 728.")
                    ]
                ]);
                break;
            case self::NOT_ENOUGH_CREDITS_HIGHLIGHT:
                $this->setMessages([
                    'expirationDate' => [
                        $translate->translate("Unfortunately there are not enough days available to highlight this vacancy.")
                    ]
                ]);
                break;
            case self::NOT_ENOUGH_CREDITS_BANNER:
                $this->setMessages([
                    'expirationDate' => [
                        $translate->translate("Unfortunately there are not enough days available to add this banner.")
                    ]
                ]);
                break;
            case self::START_DATE_IN_PAST:
                $this->setMessages([
                    'startDate' => [
                        $translate->translate("Please make sure the starting date is after today.")
                    ]
                ]);
                break;
            case self::COMPANY_HAS_THREE_HIGHLIGHTS:
                $this->setMessages([
                    'vacancy_id' => [
                        $translate->translate("Unfortunately you can place at most 3 highlights, which you already have")
                    ]
                ]);
                break;
            case self::ALREADY_THREE_HIGHLIGHTS_IN_CATEGORY:
                $this->setMessages([
                    'vacancy_id' => [
                        $translate->translate("There are already a maximum of 3 vacancies highlighted in this vacancies' category")
                    ]
                ]);
                break;
        }
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
