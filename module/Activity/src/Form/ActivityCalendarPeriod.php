<?php

namespace Activity\Form;

use DateTime;
use Exception;
use Laminas\Form\Element\{
    Collection,
    DateTimeLocal,
    Number,
    Submit};
use Laminas\Form\Form;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Mvc\I18n\Translator;
use Laminas\Validator\{
    Callback,
    Date,
};

class ActivityCalendarPeriod extends Form implements InputFilterProviderInterface
{
    /**
     * @var Translator
     */
    private Translator $translator;

    public function __construct(Translator $translator)
    {
        parent::__construct();
        $this->translator = $translator;

        $this->add(
            [
                'name' => 'beginPlanningTime',
                'type' => DateTimeLocal::class,
                'options' => [
                    'format' => 'Y-m-d\TH:i',
                    'label' => $this->translator->translate('Start date and time of planning period'),
                ],
            ]
        );

        $this->add(
            [
                'name' => 'endPlanningTime',
                'type' => DateTimeLocal::class,
                'options' => [
                    'format' => 'Y-m-d\TH:i',
                    'label' => $this->translator->translate('End date and time of planning period'),
                ],
            ]
        );

        $this->add(
            [
                'name' => 'beginOptionTime',
                'type' => DateTimeLocal::class,
                'options' => [
                    'format' => 'Y-m-d\TH:i',
                    'label' => $this->translator->translate('Start date and time of option period'),
                ],
            ]
        );

        $this->add(
            [
                'name' => 'endOptionTime',
                'type' => DateTimeLocal::class,
                'options' => [
                    'format' => 'Y-m-d\TH:i',
                    'label' => $this->translator->translate('End date and time of option period'),
                ],
            ]
        );

        $this->add(
            [
                'name' => 'maxActivities',
                'type' => Collection::class,
                'options' => [
                    'allow_add' => false,
                    'allow_remove' => false,
                    'count' => 0,
                    'should_create_template' => false,
                    'target_element' => [
                        'type' => MaxActivities::class,
                    ],
                ],
            ]
        );

        $this->add(
            [
                'name' => 'submit',
                'type' => Submit::class,
                'attributes' => [
                    'value' => $this->translator->translate('Create Option Period'),
                ],
            ]
        );
    }

    /**
     * @return array
     */
    public function getInputFilterSpecification(): array
    {
        return [
            'beginPlanningTime' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => Date::class,
                        'options' => [
                            'format' => 'Y-m-d\TH:i',
                        ]
                    ],
                    [
                        'name' => Callback::class,
                        'options' => [
                            'messages' => [
                                Callback::INVALID_VALUE => $this->translator->translate('The planning period must start after now.'),
                            ],
                            'callback' => [$this, 'afterOtherTime'],
                            'callbackOptions' => ['now'],
                        ],
                    ],
                ],
            ],
            'endPlanningTime' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => Date::class,
                        'options' => [
                            'format' => 'Y-m-d\TH:i',
                        ]
                    ],
                    [
                        'name' => Callback::class,
                        'options' => [
                            'messages' => [
                                Callback::INVALID_VALUE => $this->translator->translate('The planning period must end after it starts.'),
                            ],
                            'callback' => [$this, 'afterOtherTime'],
                            'callbackOptions' => ['beginPlanningTime'],
                        ],
                    ],
                ],
            ],
            'beginOptionTime' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => Date::class,
                        'options' => [
                            'format' => 'Y-m-d\TH:i',
                        ]
                    ],
                    [
                        'name' => Callback::class,
                        'options' => [
                            'messages' => [
                                Callback::INVALID_VALUE => $this->translator->translate('The option period must start after the planning period ends.'),
                            ],
                            'callback' => [$this, 'afterOtherTime'],
                            'callbackOptions' => ['endPlanningTime'],
                        ],
                    ],
                ],
            ],
            'endOptionTime' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => Date::class,
                        'options' => [
                            'format' => 'Y-m-d\TH:i',
                        ]
                    ],
                    [
                        'name' => Callback::class,
                        'options' => [
                            'messages' => [
                                Callback::INVALID_VALUE => $this->translator->translate('The option period must end after it starts.'),
                            ],
                            'callback' => [$this, 'afterOtherTime'],
                            'callbackOptions' => ['beginOptionTime'],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param string $value
     * @param array $context
     * @param string $option
     *
     * @return bool
     */
    public function afterOtherTime(string $value, array $context, string $option): bool
    {
        try {
            $value = new DateTime($value);
            $time = isset($context[$option]) ? new DateTime($context[$option]) : new DateTime('now');

            return $value > $time;
        } catch (Exception $e) {
            // An exception is an indication that one of the DateTimes was not valid
            return false;
        }
    }
}
