<?php

declare(strict_types=1);

namespace Activity\Form;

use Activity\Service\ActivityCalendarForm;
use DateTime;
use Laminas\Form\Element\Date;
use Laminas\Form\Element\Select;
use Laminas\Form\Fieldset;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Mvc\I18n\Translator;
use Laminas\Validator\Callback;
use Throwable;

class ActivityCalendarOption extends Fieldset implements InputFilterProviderInterface
{
    public function __construct(
        private readonly Translator $translator,
        private readonly ActivityCalendarForm $calendarFormService,
    ) {
        parent::__construct();

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
            ],
        );

        $this->add(
            [
                'name' => 'endTime',
                'type' => Date::class,
                'options' => [
                    'format' => 'Y-m-d',
                ],
            ],
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
            ],
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
                                Callback::INVALID_VALUE => $this->translator->translate(
                                    'The activity must start before it ends',
                                ),
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
                                Callback::INVALID_VALUE => $this->translator->translate(
                                    'The activity must start after today',
                                ),
                            ],
                            'callback' => function ($value, $context = []) {
                                return $this->isFutureTime($value, $context);
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
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    public function beforeEndTime(
        string $value,
        array $context = [],
    ): bool {
        try {
            $endTime = isset($context['endTime']) ? $this->calendarFormService->toDateTime(
                $context['endTime'],
            ) : new DateTime('now');

            return $this->calendarFormService->toDateTime($value) <= $endTime;
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Check if a certain date is in the future.
     */
    public function isFutureTime(string $value): bool
    {
        try {
            $today = new DateTime();

            return $this->calendarFormService->toDateTime($value) > $today;
        } catch (Throwable) {
            return false;
        }
    }
}
