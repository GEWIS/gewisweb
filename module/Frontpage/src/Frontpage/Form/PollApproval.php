<?php

namespace Frontpage\Form;

use Laminas\Form\Form;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\I18n\Translator\TranslatorInterface as Translator;

class PollApproval extends Form implements InputFilterProviderInterface
{
    public function __construct(Translator $translator)
    {
        parent::__construct();

        $this->add(
            [
            'name' => 'expiryDate',
            'type' => 'Laminas\Form\Element\Date',
            'options' => [
                'label' => $translator->translate('Expiration date for the poll (YYYY-MM-DD)')
            ]
            ]
        );

        $this->add(
            [
            'name' => 'submit',
            'type' => 'submit',
            'options' => [
                'label' => $translator->translate('Approve poll')
            ]
            ]
        );
    }

    /**
     * Should return an array specification compatible with
     * {@link \Laminas\InputFilter\Factory::createInputFilter()}.
     *
     * @return array
     */
    public function getInputFilterSpecification()
    {
        return [
            'expiryDate' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => 'date',
                    ],
                ],
            ],
        ];
    }
}
