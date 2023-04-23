<?php

declare(strict_types=1);

namespace Frontpage\Form;

use Laminas\Form\Element\{
    Date,
    Submit,
};
use Laminas\Form\Form;
use Laminas\Mvc\I18n\Translator;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Validator\Date as DateValidator;

class PollApproval extends Form implements InputFilterProviderInterface
{
    public function __construct(Translator $translator)
    {
        parent::__construct();

        $this->add(
            [
                'name' => 'expiryDate',
                'type' => Date::class,
                'options' => [
                    'label' => $translator->translate('Expiration date for the poll (YYYY-MM-DD)'),
                    'format' => 'Y-m-d',
                ],
            ]
        );

        $this->add(
            [
                'name' => 'submit',
                'type' => Submit::class,
                'options' => [
                    'label' => $translator->translate('Approve poll'),
                ],
            ]
        );
    }

    /**
     * Should return an array specification compatible with
     * {@link \Laminas\InputFilter\Factory::createInputFilter()}.
     *
     * @return array
     */
    public function getInputFilterSpecification(): array
    {
        return [
            'expiryDate' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => DateValidator::class,
                    ],
                ],
            ],
        ];
    }
}
