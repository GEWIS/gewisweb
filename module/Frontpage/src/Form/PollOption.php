<?php

declare(strict_types=1);

namespace Frontpage\Form;

use Frontpage\Model\PollOption as PollOptionModel;
use Laminas\Filter\StringTrim;
use Laminas\Form\Element\Text;
use Laminas\Form\Fieldset;
use Laminas\Hydrator\ClassMethodsHydrator;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Mvc\I18n\Translator;
use Laminas\Validator\StringLength;

class PollOption extends Fieldset implements InputFilterProviderInterface
{
    public function __construct(private readonly Translator $translator)
    {
        parent::__construct('pollOption');

        $this->setHydrator(new ClassMethodsHydrator(false))
            ->setObject(new PollOptionModel());

        $this->add(
            [
                'name' => 'dutchText',
                'type' => Text::class,
                'options' => [
                    'label' => $this->translator->translate('Option %s'),
                ],
            ],
        );

        $this->add(
            [
                'name' => 'englishText',
                'type' => Text::class,
                'options' => [
                    'label' => $this->translator->translate('Option %s'),
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
            'dutchText' => [
                'required' => true,
                'filters' => [
                    [
                        'name' => StringTrim::class,
                    ],
                ],
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
                'filters' => [
                    [
                        'name' => StringTrim::class,
                    ],
                ],
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
