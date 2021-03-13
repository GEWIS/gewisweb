<?php

namespace Frontpage\Form;

use Zend\Form\Form;
use Zend\InputFilter\InputFilterProviderInterface;
use Zend\I18n\Translator\TranslatorInterface as Translator;

class PollApproval extends Form implements InputFilterProviderInterface
{
    public function __construct(Translator $translator)
    {
        parent::__construct();

        $this->add([
            'name' => 'expiryDate',
            'type' => 'Zend\Form\Element\Date',
            'options' => [
                'label' => $translator->translate('Expiration date for the poll (YYYY-MM-DD)')
            ]
        ]);

        $this->add([
            'name' => 'submit',
            'type' => 'submit',
            'options' => [
                'label' => $translator->translate('Approve poll')
            ]
        ]);
    }

    /**
     * Should return an array specification compatible with
     * {@link Zend\InputFilter\Factory::createInputFilter()}.
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
