<?php

namespace User\Form;

use Zend\Form\Form;
use Zend\InputFilter\InputFilter;
use Zend\I18n\Translator\TranslatorInterface as Translator;

class CompanyPassword extends Form
{
    const ERROR_WRONG_EMAIL = 'wrong_email';
    const ERROR_MEMBER_NOT_EXISTS = 'member_not_exists';
    const ERROR_USER_ALREADY_EXISTS = 'user_already_exists';
    const ERROR_ALREADY_REGISTERED = 'already_registered';

    protected $translate;

    public function __construct(Translator $translate)
    {
        parent::__construct();
        $this->translate = $translate;

        $this->add([
            'name' => 'email',
            'type' => 'email',
            'options' => [
                'label' => $translate->translate('E-mail address')
            ]
        ]);

        $this->add([
            'name' => 'submit',
            'type' => 'submit',
            'attributes' => [
                'value' => $translate->translate('Register')
            ]
        ]);

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
                $this->setMessages([
                    'email' => [
                        $this->translate->translate("This email address does not be long to the given member.")
                    ]
                ]);
                break;
                    }
    }

    protected function initFilters()
    {
        $filter = new InputFilter();

        $filter->add([
            'name' => 'email',
            'required' => true,
            'validators' => [
                ['name' => 'not_empty'],
                ['name' => 'email_address']
            ]
        ]);

        $this->setInputFilter($filter);
    }
}
