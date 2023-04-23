<?php

declare(strict_types=1);

namespace Photo\Form;

use Laminas\Form\Element\{
    DateTimeLocal,
    Submit,
    Text,
};
use Laminas\Form\Form;
use Laminas\I18n\Validator\Alnum;
use Laminas\InputFilter\InputProviderInterface;
use Laminas\Mvc\I18n\Translator;
use Laminas\Validator\NotEmpty;

class EditAlbum extends Form implements InputProviderInterface
{
    public function __construct(Translator $translate)
    {
        parent::__construct();

        $this->add(
            [
                'name' => 'name',
                'type' => Text::class,
                'options' => [
                    'label' => $translate->translate('Album title'),
                ],
            ]
        );

        $this->add(
            [
                'name' => 'startDateTime',
                'type' => DateTimeLocal::class,
                'options' => [
                    'label' => $translate->translate('Start date'),
                    'format' => 'Y-m-d\TH:i',
                ],
            ]
        );

        $this->add(
            [
                'name' => 'endDateTime',
                'type' => DateTimeLocal::class,
                'options' => [
                    'label' => $translate->translate('End date'),
                    'format' => 'Y-m-d\TH:i',
                ],
            ]
        );

        $this->add(
            [
                'name' => 'submit',
                'type' => Submit::class,
                'options' => [
                    'label' => $translate->translate('Save'),
                ],
            ]
        );
    }

    /**
     * @return array
     */
    public function getInputSpecification(): array
    {
        return [
            'name' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => NotEmpty::class,
                    ],
                    [
                        'name' => Alnum::class,
                        'options' => [
                            'allowWhiteSpace' => true,
                        ],
                    ],
                ],
            ],
        ];
    }
}
