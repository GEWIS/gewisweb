<?php

namespace Activity\Form;

use Activity\Service\ActivityCalendarForm;
use DateTime;
use Exception;
use Laminas\Form\Element\{
    Date,
    Select,
};
use Laminas\Form\Fieldset;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Mvc\I18n\Translator;
use Laminas\Validator\Callback;

class ActivityCalendarOption extends Fieldset implements InputFilterProviderInterface
{
    /**
     * @var Translator
     */
    protected Translator $translator;

    /**
     * @var ActivityCalendarForm
     */
    private ActivityCalendarForm $calendarFormService;

    /**
     * ActivityCalendarOption constructor.
     *
     * @param Translator $translator
     * @param ActivityCalendarForm $calendarFormService
     */
    public function __construct(
        Translator $translator,
        ActivityCalendarForm $calendarFormService,
    ) {
        parent::__construct();
        $this->translator = $translator;
        $this->calendarFormService = $calendarFormService;

        $typeOptions = [
            'Morning' => $translator->translate('Morning'),
            'Lunch break' => $translator->translate('Lunch break'),
            'Afternoon' => $translator->translate('Afternoon'),
            'Evening' => $translator->translate('Evening'),
            'Day' => $translator->translate('Day'),
            'Multiple days' => $translator->translate('Multiple days'),
        ];

        $this->add(
            [
                'name' => 'beginTime',
                'type' => Date::class,
                'options' => [
                    'format' => 'Y-m-d',
                ],
            ]
        );

        $this->add(
            [
                'name' => 'endTime',
                'type' => Date::class,
                'options' => [
                    'format' => 'Y-m-d',
                ],
            ]
        );

        $this->add(
            [
                'name' => 'type',
                'type' => Select::class,
                'options' => [
                    'empty_option' => [
                        'label' => $translator->translate('Select a type'),
                        'selected' => 'selected',
                        'disabled' => 'disabled',
                    ],
                    'value_options' => $typeOptions,
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
            'beginTime' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => Callback::class,
                        'options' => [
                            'messages' => [
                                Callback::INVALID_VALUE => $this->translator->translate('The activity must start before it ends'),
                            ],
                            'callback' => function ($value, $context = []) {
                                return $this->beforeEndTime($value, $context);
                            },
                        ],
                    ],
                    [
                        'name' => Callback::class,
                        'options' => [
                            'messages' => [
                                Callback::INVALID_VALUE => $this->translator->translate('The activity must start after today'),
                            ],
                            'callback' => function ($value, $context = []) {
                                return $this->isFutureTime($value, $context);
                            },
                        ],
                    ],
                    [
                        'name' => Callback::class,
                        'options' => [
                            'messages' => [
                                Callback::INVALID_VALUE => $this->translator->translate('The activity must be within the given period'),
                            ],
                            'callback' => function ($value, $context = []) {
                                return $this->cannotPlanInPeriod($value, $context);
                            },
                        ],
                    ],
                ],
            ],
            'endTime' => [
                'required' => true,
            ],

            'type' => [
                'required' => true,
            ],
        ];
    }

    /**
     * Check if a certain date is before the end date of the option.
     *
     * @param string $value
     * @param array $context
     *
     * @return bool
     */
    public function beforeEndTime(
        string $value,
        array $context = [],
    ): bool {
        try {
            $endTime = isset($context['endTime']) ? $this->calendarFormService->toDateTime($context['endTime']) : new DateTime('now');

            return $this->calendarFormService->toDateTime($value) <= $endTime;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Check if a certain date is in the future.
     *
     * @param string $value
     * @param array $context
     *
     * @return bool
     */
    public function isFutureTime(
        string $value,
        array $context = [],
    ): bool {
        try {
            $today = new DateTime();

            return $this->calendarFormService->toDateTime($value) > $today;
        } catch (Exception) {
            return false;
        }
    }

    /**
     * Check if a certain date is within the current planning period.
     *
     * @param string $value
     * @param array $context
     *
     * @return bool
     */
    public function cannotPlanInPeriod(
        string $value,
        array $context = [],
    ): bool {
        try {
            $beginTime = $this->calendarFormService->toDateTime($value);
            $result = $this->calendarFormService->canCreateOption($beginTime);

            return !$result;
        } catch (Exception $e) {
            return false;
        }
    }
}
