<?php

namespace Decision\Form;

use Zend\Form\Form;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterProviderInterface;
use Zend\I18n\Translator\TranslatorInterface as Translator;

class SearchDecision extends Form implements InputFilterProviderInterface
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

        $this->add([
            'name' => 'submit',
            'type' => 'submit',
            'attributes' => [
                'value' => $translate->translate('Search'),
                'label' => $translate->translate('Search')
            ]
        ]);
    }

    /**
     * Input filter specification.
     */
    public function getInputFilterSpecification()
    {
        return [
            'query' => [
                'required' => true,
                'validators' => [
                    ['name' => 'not_empty']
                ]
            ]
        ];
    }
}
