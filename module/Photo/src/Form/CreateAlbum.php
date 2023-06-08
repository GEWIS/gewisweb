<?php

declare(strict_types=1);

namespace Photo\Form;

use Laminas\Form\Element\Submit;
use Laminas\Form\Element\Text;
use Laminas\Form\Form;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Mvc\I18n\Translator;
use Laminas\Validator\StringLength;

class CreateAlbum extends Form implements InputFilterProviderInterface
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
                'name' => 'submit',
                'type' => Submit::class,
                'options' => [
                    'label' => $translate->translate('Create'),
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
                        'name' => StringLength::class,
                        'options' => [
                            'encoding' => 'UTF-8',
                            'min' => 3,
                            'max' => 75,
                        ],
                    ],
                ],
            ],
        ];
    }
}
