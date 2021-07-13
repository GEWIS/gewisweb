<?php

namespace Decision\Form;

use Laminas\Form\Form;
use Laminas\I18n\Translator\TranslatorInterface as Translator;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Validator\NotEmpty;

class SearchDecision extends Form implements InputFilterProviderInterface
{
    public function __construct(Translator $translate)
    {
        parent::__construct();

        $this->add(
            [
            'name' => 'query',
            'type' => 'text',
            'options' => [
                'label' => $translate->translate('Search query'),
            ],
            ]
        );

        $this->add(
            [
            'name' => 'submit',
            'type' => 'submit',
            'attributes' => [
                'value' => $translate->translate('Search'),
                'label' => $translate->translate('Search'),
            ],
            ]
        );
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
                    ['name' => NotEmpty::class],
                ],
            ],
        ];
    }
}
