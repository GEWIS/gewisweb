<?php

namespace Activity\Form;

use Zend\Form\Fieldset;
use Zend\InputFilter\InputFilterProviderInterface;
use Zend\Mvc\I18n\Translator;

class ActivityCalendarOption extends Fieldset implements InputFilterProviderInterface
{
    protected $translator;

    /**
     * ActivityCalendarOption constructor.
     *
     * @param Translator $translator
     * @param \Activity\Service\ActivityCalendar $calendarService
     */
    public function __construct(Translator $translator, $calendarService)
    {
        parent::__construct();
        $this->translator = $translator;
        $this->calendarService = $calendarService;

        $typeOptions = [
            $translator->translate('Lunch lecture'),
            $translator->translate('Morning'),
            $translator->translate('Afternoon'),
            $translator->translate('Evening'),
            $translator->translate('Weekend'),
        ];

        $this->add([
            'name' => 'beginTime',
            'type' => 'datetime',
            'options' => [
                'format' => 'Y/m/d'
            ],
        ]);

        $this->add([
            'name' => 'endTime',
            'type' => 'datetime',
            'options' => [
                'format' => 'Y/m/d'
            ],
        ]);

        $this->add([
            'name' => 'type',
            'type' => 'select',
            'options' => [
                'empty_option' => [
                    'label'    => $translator->translate('Select a type'),
                    'selected' => 'selected',
                    'disabled' => 'disabled',
                ],
                'value_options' => $typeOptions
            ]
        ]);
    }

    public function getInputFilterSpecification()
    {
        return [
            'beginTime' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => 'callback',
                        'options' => [
                            'messages' => [
                                \Zend\Validator\Callback::INVALID_VALUE =>
                                    $this->translator->translate('The activity must start before it ends'),
                            ],
                            'callback' => function ($value, $context = []) {
                                return $this->beforeEndTime($value, $context);
                            }
                        ],
                    ],
                    [
                        'name' => 'callback',
                        'options' => [
                            'messages' => [
                                \Zend\Validator\Callback::INVALID_VALUE =>
                                    $this->translator->translate('The activity must start after today'),
                            ],
                            'callback' => function ($value, $context = []) {
                                return $this->isFutureTime($value, $context);
                            }
                        ],
                    ],
//                    [
//                        'name' => 'callback',
//                        'options' => [
//                            'messages' => [
//                                \Zend\Validator\Callback::INVALID_VALUE =>
//                                    $this->translator->translate('The activity must be within the given period'),
//                            ],
//                            'callback' => function ($value, $context = []) {
//                                return $this->cannotPlanInPeriod($value, $context);
//                            }
//                        ],
//                    ],
                ]
            ],
            'endTime' => [
                'required' => true,
            ],

            'type' => [
                'required' => true
            ],
        ];
    }

    /**
     * Check if a certain date is before the end date of the option
     *
     * @param $value
     * @param array $context
     * @return bool
     */
    public function beforeEndTime($value, $context = [])
    {
        try {
            $endTime = isset($context['endTime']) ? $this->calendarService->toDateTime($context['endTime']) : new \DateTime('now');

            return $this->calendarService->toDateTime($value) <= $endTime;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check if a certain date is in the future
     *
     * @param $value
     * @param array $context
     * @return bool
     */
    public function isFutureTime($value, $context = [])
    {
        try {
            $today = new \DateTime();

            return $this->calendarService->toDateTime($value) > $today;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check if a certain date is within the current planning period
     *
     * @param $value
     * @param array $context
     * @param \Activity\Service\ActivityCalendar $calendarService
     * @return bool
     */
    public function cannotPlanInPeriod($value, $context = [])
    {
        try {
            $begin_time = $this->calendarService->toDateTime($value);
            $result = $this->calendarService->canCreateOption($begin_time);
            return !$result;
        } catch (\Exception $e) {
            return false;
        }
    }
}