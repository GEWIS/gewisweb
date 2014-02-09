<?php

namespace User\Form;

use Zend\Form\Form;
use Zend\InputFilter\InputFilter;
use Zend\I18n\Translator\Translator;

class Logout extends Form
{

    public function __construct(Translator $translate)
    {
        parent::__construct();

        $this->add(array(
            'name' => 'submit_yes',
            'type' => 'submit',
            'attributes' => array(
                'value' => $translate->translate('Yes')
            )
        ));

        $this->add(array(
            'name' => 'submit_no',
            'type' => 'submit',
            'attributes' => array(
                'value' => $translate->translate('No')
            )
        ));
        $this->initFilters();
    }

    protected function initFilters()
    {
        $filter = new InputFilter();

        // this filter makes sure that the form is only valid when the user
        // has clicked yes, and thus can be logged out
        $filter->add(array(
            'name' => 'submit_yes',
            'required' => true
        ));

        $this->setInputFilter($filter);
    }
}
