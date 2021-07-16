<?php

namespace Frontpage\Form;

use Frontpage\Model\PollOption as PollOptionModel;
use Laminas\Form\Fieldset;
use Laminas\Hydrator\ClassMethodsHydrator;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Validator\StringLength;

class PollOption extends Fieldset implements InputFilterProviderInterface
{
    public function __construct()
    {
        parent::__construct('pollOption');

        $this->setHydrator(new ClassMethodsHydrator(false))
            ->setObject(new PollOptionModel());

        $this->add(
            [
                'name' => 'dutchText',
                'type' => 'text',
                'options' => [
                    'label' => 'Dutch option',
                ],
            ]
        );

        $this->add(
            [
                'name' => 'englishText',
                'type' => 'text',
                'options' => [
                    'label' => 'English option',
                ],
            ]
        );
    }

    /**
     * @return array
     */
    public function getInputFilterSpecification()
    {
        return [
            'dutchText' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => StringLength::class,
                        'options' => [
                            'min' => 2,
                            'max' => 128,
                        ],
                    ],
                ],
            ],
            'englishText' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => StringLength::class,
                        'options' => [
                            'min' => 2,
                            'max' => 128,
                        ],
                    ],
                ],
            ],
        ];
    }
}
