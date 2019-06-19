<?php

namespace Activity\Form;

use Decision\Model\Organ;
use Zend\Form\Form;
use Zend\Mvc\I18n\Translator;
use Zend\InputFilter\InputFilterProviderInterface;

class ActivityCalendarProposal extends Form implements InputFilterProviderInterface
{
    protected $translator;

    /**
     * @param Organ[] $organs
     * @param Translator $translator
     */
    public function __construct(array $organs, Translator $translator)
    {
        parent::__construct();
        $this->translator = $translator;

        $organOptions = [];
        foreach ($organs as $organ) {
            $organOptions[$organ->getId()] = $organ->getAbbr();
        }

        $this->add([
            'name' => 'organ',
            'type' => 'select',
            'options' => [
                'empty_option' => [
                    'label'    => $translator->translate('Select an option'),
                    'selected' => 'selected',
                    'disabled' => 'disabled',
                ],
                'value_options' => $organOptions
            ]
        ]);

        $this->add([
            'name' => 'name',
            'attributes' => [
                'type' => 'text',
            ],
        ]);

        $this->add([
            'name' => 'description',
            'attributes' => [
                'type' => 'text',
            ],
        ]);

        $this->add([
            'name' => 'options',
            'type' => 'Zend\Form\Element\Collection',
            'options' => [
                'count' => 1,
                'should_create_template' => true,
                'allow_add' => true,
                'target_element' => new ActivityCalendarOption($translator)
            ]
        ]);
    }

    /**
     * Input filter specification.
     */
    public function getInputFilterSpecification()
    {
        return [
            'organ' => [
                'required' => true
            ],
            'name' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => 'string_length',
                        'options' => [
                            'min' => 2,
                            'max' => 128
                        ]
                    ]
                ]
            ],
            'description' => [
                'required' => false
            ],
        ];
    }
}
