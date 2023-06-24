<?php

declare(strict_types=1);

namespace Photo\Form;

use Laminas\Form\Element\DateTimeLocal;
use Laminas\Form\Element\Submit;
use Laminas\Form\Element\Text;
use Laminas\Form\Form;
use Laminas\I18n\Validator\Alnum;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Mvc\I18n\Translator;
use Laminas\Validator\NotEmpty;

class EditAlbum extends Form implements InputFilterProviderInterface
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
            ],
        );

        $this->add(
            [
                'name' => 'startDateTime',
                'type' => DateTimeLocal::class,
                'options' => [
                    'label' => $translate->translate('Start date'),
                    'format' => 'Y-m-d\TH:i',
                ],
            ],
        );

        $this->add(
            [
                'name' => 'endDateTime',
                'type' => DateTimeLocal::class,
                'options' => [
                    'label' => $translate->translate('End date'),
                    'format' => 'Y-m-d\TH:i',
                ],
            ],
        );

        $this->add(
            [
                'name' => 'submit',
                'type' => Submit::class,
                'options' => [
                    'label' => $translate->translate('Save'),
                ],
            ],
        );
    }

    /**
     * @return array
     */
    public function getInputFilterSpecification(): array
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
