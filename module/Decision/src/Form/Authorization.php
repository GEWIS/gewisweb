<?php

namespace Decision\Form;

use Laminas\Form\Element\{
    Checkbox,
    Csrf,
    Hidden,
    Submit,
};
use Laminas\Form\Form;
use Laminas\Mvc\I18n\Translator;
use Laminas\InputFilter\InputFilterProviderInterface;

class Authorization extends Form implements InputFilterProviderInterface
{
    /**
     * @param Translator $translate
     */
    public function __construct(Translator $translate)
    {
        parent::__construct();

        $this->add(
            [
                'name' => 'recipient',
                'type' => Hidden::class,
            ]
        );

        $this->add(
            [
                'name' => 'agree',
                'type' => Checkbox::class,
                'options' => [
                    'use_hidden_element' => false,
                ],
            ]
        );

        $this->add(
            [
                'name' => 'csrf_token',
                'type' => Csrf::class,
            ]
        );

        $this->add(
            [
                'name' => 'submit',
                'type' => Submit::class,
                'attributes' => [
                    'label' => $translate->translate('Authorize'),
                ],
            ]
        );
    }

    /**
     * Input filter specification.
     *
     * @return array
     */
    public function getInputFilterSpecification(): array
    {
        return [
            'agree' => [
                'required' => true,
            ],
        ];
    }
}
