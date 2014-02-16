<?php

namespace User\Form;

use Zend\Form\Form;
use Zend\InputFilter\InputFilter;
use Zend\I18n\Translator\Translator;

class Register extends Form
{

    const ERROR_WRONG_EMAIL = 'wrong_email';
    const ERROR_MEMBER_NOT_EXISTS = 'member_not_exists';
    const ERROR_USER_ALREADY_EXISTS = 'user_already_exists';

    protected $translate;

    public function __construct(Translator $translate)
    {
        parent::__construct();
        $this->translate = $translate;

        $this->add(array(
            'name' => 'lidnr',
            'type' => 'number',
            'options' => array(
                'label' => $translate->translate('Membership number')
            )
        ));

        $this->add(array(
            'name' => 'email',
            'type' => 'email',
            'options' => array(
                'label' => $translate->translate('E-mail address')
            )
        ));

        $this->add(array(
            'name' => 'submit',
            'type' => 'submit',
            'attributes' => array(
                'value' => $translate->translate('Register')
            )
        ));

        $this->initFilters();
    }

    /**
     * Set the error.
     *
     * @param string $error
     */
    public function setError($error)
    {
        switch ($error) {
        case self::ERROR_WRONG_EMAIL:
            $this->setMessages(array(
                'email' => array(
                    $this->translate->translate("This email address does not be long to the given member.")
                )
            ));
            break;
        case self::ERROR_MEMBER_NOT_EXISTS:
            $this->setMessages(array(
                'lidnr' => array(
                    $this->translate->translate("There is no member with this membership number.")
                )
            ));
            break;
        case self::ERROR_USER_ALREADY_EXISTS:
            $this->setMessages(array(
                'lidnr' => array(
                    $this->translate->translate("This member already has an account.")
                )
            ));
            break;
        }
    }

    protected function initFilters()
    {
        $filter = new InputFilter();

        $filter->add(array(
            'name' => 'lidnr',
            'required' => true,
            'validators' => array(
                array('name' => 'not_empty'),
                array('name' => 'digits')
            )
        ));

        $filter->add(array(
            'name' => 'email',
            'required' => true,
            'validators' => array(
                array('name' => 'not_empty'),
                array('name' => 'email_address')
            )
        ));

        $this->setInputFilter($filter);
    }
}


