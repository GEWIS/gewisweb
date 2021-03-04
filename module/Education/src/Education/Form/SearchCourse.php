<?php

namespace Education\Form;

use Zend\Form\Form;
use Zend\InputFilter\InputFilter;
use Zend\I18n\Translator\TranslatorInterface as Translator;

class SearchCourse extends Form
{
    public function __construct(Translator $translate)
    {
        parent::__construct();

        $this->add([
            'name' => 'query',
            'type' => 'text',
            'options' => [
                'label' => $translate->translate('Search query')
            ]
        ]);

        $this->initFilters();
    }

    protected function initFilters()
    {
        $filter = new InputFilter();

        $filter->add([
            'name' => 'query',
            'required' => true,
            'validators' => [
                ['name' => 'not_empty'],
            ]
        ]);

        $this->setInputFilter($filter);
    }
}
