<?php

namespace Decision\Form;

use Laminas\Form\Form;
use Laminas\I18n\Translator\TranslatorInterface as Translator;
use Laminas\InputFilter\InputFilterProviderInterface;

class Authorization extends Form implements InputFilterProviderInterface
{
    public function __construct(Translator $translate)
    {
        parent::__construct();

        $this->add(
            [
                'name' => 'recipient',
                'type' => 'hidden',
            ]
        );

        $this->add(
            [
                'name' => 'agree',
                'type' => 'checkbox',
                'options' => [
                    'use_hidden_element' => false,
                ],
            ]
        );

        $this->add(
            [
                'name' => 'submit',
                'type' => 'submit',
                'attributes' => [
                    'label' => $translate->translate('Authorize'),
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
            'agree' => [
                'required' => true,
            ],
        ];
    }
}
