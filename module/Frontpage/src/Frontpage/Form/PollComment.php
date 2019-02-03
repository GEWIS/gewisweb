<?php

namespace Frontpage\Form;

use Zend\Form\Form;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterProviderInterface;
use Zend\I18n\Translator\TranslatorInterface as Translator;

class PollComment extends Form implements InputFilterProviderInterface
{

    public function __construct(Translator $translator)
    {
        parent::__construct();

        $this->add([
            'name' => 'author',
            'type' => 'text',
            'options' => [
                'label' => $translator->translate('Author')
            ]
        ]);

        $this->add([
            'name' => 'content',
            'type' => 'textarea',
            'options' => [
                'label' => $translator->translate('Content')
            ]
        ]);

        $this->add([
            'name' => 'submit',
            'type' => 'submit',
            'attributes' => [
                'value' => $translator->translate('Comment')
            ]
        ]);
        $this->get('submit')->setLabel($translator->translate('Comment'));
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
            'author' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => 'string_length',
                        'options' => [
                            'min' => 2,
                            'max' => 32
                        ]
                    ]
                ]
            ],
            'content' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => 'string_length',
                        'options' => [
                            'min' => 8
                        ]
                    ]
                ]
            ],
        ];
    }
}
