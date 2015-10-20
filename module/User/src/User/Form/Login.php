<?php

namespace User\Form;

use Zend\Form\Form;
use Zend\InputFilter\InputFilter;
use Zend\I18n\Translator\TranslatorInterface as Translator;
use Zend\Authentication\Result;

class Login extends Form
{

    protected $translate;

    public function __construct(Translator $translate)
    {
        parent::__construct();
        $this->translate = $translate;

        $this->add(array(
            'name' => 'login',
            'type' => 'text',
            'options' => array(
                'label' => $translate->translate('Membership number or email address')
            )
        ));

        $this->add(array(
            'name' => 'password',
            'type' => 'password',
            'options' => array(
                'label' => $translate->translate('Your password')
            )
        ));

        $this->add(array(
            'name' => 'submit',
            'type' => 'submit',
            'attributes' => array(
                'value' => $translate->translate('Login')
            )
        ));

        $this->add(array(
            'name' => 'redirect',
            'type' => 'hidden'
        ));

        $this->initFilters();
    }

    /**
     * Set authentication result.
     */
    public function setResult(Result $result)
    {
        if (!$result->isValid()) {
            switch ($result->getCode()) {
            case Result::FAILURE_IDENTITY_NOT_FOUND:
                $this->setMessages(array(
                    'login' => array(
                        $this->translate->translate('This user could not be found.')
                    )
                ));
                break;
            case Result::FAILURE_CREDENTIAL_INVALID:
                $this->setMessages(array(
                    'password' => array(
                        $this->translate->translate('Wrong password provided.')
                    )
                ));
                break;
            }
        }
    }

    protected function initFilters()
    {
        $filter = new InputFilter();

        $filter->add(array(
            'name' => 'login',
            'required' => true,
            'validators' => array(
                array('name' => 'not_empty')
            )
        ));

        $filter->add(array(
            'name' => 'password',
            'required' => true,
            'validators' => array(
                array('name' => 'not_empty'),
                array(
                    'name' => 'string_length',
                    'options' => array(
                        'min' => 8
                    )
                )
            )
        ));

        $this->setInputFilter($filter);
    }
}

