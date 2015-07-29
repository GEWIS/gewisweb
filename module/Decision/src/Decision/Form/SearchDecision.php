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
                'value' => $translate->translate('Search'),
                'label' => $translate->translate('Search')
            )
        ));
    }

    /**
     * Input filter specification.
     */
    public function getInputFilterSpecification()
    {
        return array(
            'query' => array(
                'required' => true,
                'validators' => array(
                    array('name' => 'not_empty')
                )
            )
        );
    }
}
