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

        $this->add([
            'name' => 'login',
            'type' => 'text',
            'options' => [
                'label' => $translate->translate('Membership number or email address')
            ]
        ]);

        $this->add([
            'name' => 'password',
            'type' => 'password',
            'options' => [
                'label' => $translate->translate('Your password')
            ]
        ]);

        $this->add([
            'name' => 'submit',
            'type' => 'submit',
            'attributes' => [
                'value' => $translate->translate('Login')
            ]
        ]);

        $this->add([
            'name' => 'remember',
            'type' => 'checkbox',
            'options' => [
                'label' => $translate->translate('Remember me'),
                'checked_value' => 1,
                'unchecked_value' => 0,
                'checked' => true
            ],
        ]);

        $this->add([
            'name' => 'redirect',
            'type' => 'hidden'
        ]);

        $this->add([
            'name' => 'security',
            'type' => 'Zend\Form\Element\Csrf'
        ]);

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
                    $this->setMessages([
                        'login' => [
                            $this->translate->translate('This user could not be found.')
                        ]
                    ]);
                    break;
                case Result::FAILURE_CREDENTIAL_INVALID:
                    $this->setMessages([
                        'password' => [
                            $this->translate->translate('Wrong password provided.')
                        ]
                    ]);
                    break;
                case Result::FAILURE:
                    $this->setMessages([
                        'password' => [
                            $this->translate->translate('Too many login attempts, try again later.')
                        ]
                    ]);
                    break;
            }
        }
    }

    protected function initFilters()
    {
        $filter = new InputFilter();

        $filter->add([
            'name' => 'login',
            'required' => true,
            'validators' => [
                ['name' => 'not_empty']
            ]
        ]);

        $filter->add([
            'name' => 'password',
            'required' => true,
            'validators' => [
                ['name' => 'not_empty'],
                [
                    'name' => 'string_length',
                    'options' => [
                        'min' => 8
                    ]
                ]
            ]
        ]);

        $this->setInputFilter($filter);
    }
}

