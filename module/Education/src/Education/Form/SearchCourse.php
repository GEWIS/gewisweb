<?php

namespace Education\Form;

use Zend\Form\Form;
use Zend\InputFilter\InputFilter;
use Zend\I18n\Translator\Translator;

class SearchCourse extends Form
{

    public function __construct(Translator $translate)
    {
        parent::__construct();

        $this->add(array(
            'name' => 'query',
            'type' => 'text',
            'options' => array(
                'label' => $translate->translate('Search query')
            )
        ));

        $this->add(array(
            'name' => 'submit',
            'type' => 'submit',
            'attributes' => array(
                'value' => $translate->translate('Search')
            )
        ));

        $this->initFilters();
    }

    protected function initFilters()
    {
        $filter = new InputFilter();

        $filter->add(array(
            'name' => 'query',
            'required' => true,
            'validators' => array(
                array('name' => 'not_empty'),
            )
        ));

        $this->setInputFilter($filter);
    }
}
