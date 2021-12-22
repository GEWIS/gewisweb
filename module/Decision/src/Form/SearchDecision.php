<?php

namespace Decision\Form;

use Laminas\Form\Element\{
    Submit,
    Text,
};
use Laminas\Form\Form;
use Laminas\Mvc\I18n\Translator;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Validator\NotEmpty;

class SearchDecision extends Form implements InputFilterProviderInterface
{
    /**
     * @param Translator $translate
     */
    public function __construct(Translator $translate)
    {
        parent::__construct();

        $this->add(
            [
                'name' => 'query',
                'type' => Text::class,
                'options' => [
                    'label' => $translate->translate('Search query'),
                ],
            ]
        );

        $this->add(
            [
                'name' => 'submit',
                'type' => Submit::class,
                'attributes' => [
                    'value' => $translate->translate('Search'),
                    'label' => $translate->translate('Search'),
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
            'query' => [
                'required' => true,
                'validators' => [
                    ['name' => NotEmpty::class],
                ],
            ],
        ];
    }
}
