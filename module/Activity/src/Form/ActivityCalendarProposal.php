<?php

namespace Activity\Form;

use Activity\Service\ActivityCalendarForm;
use Exception;
use Laminas\Form\Element\{
    Collection,
    Select,
    Text,
};
use Laminas\Form\Form;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Mvc\I18n\Translator;
use Laminas\Validator\{
    Callback,
    StringLength,
};
use User\Permissions\NotAllowedException;

class ActivityCalendarProposal extends Form implements InputFilterProviderInterface
{
    private int $maxOptions;

    public function __construct(
        private readonly Translator $translator,
        private readonly ActivityCalendarForm $calendarFormService,
        bool $createAlways,
    ) {
        parent::__construct();

        try {
            $organs = $calendarFormService->getEditableOrgans();
        } catch (NotAllowedException $e) {
            $organs = [];
        }

        $organOptions = [];
        foreach ($organs as $organ) {
            $organOptions[$organ->getId()] = $organ->getAbbr();
        }
        if ($createAlways) {
            $organOptions[-1] = 'Board';
            $organOptions[-2] = 'Other';
        }

        $this->maxOptions = 3;

        $this->add(
            [
                'name' => 'organ',
                'type' => Select::class,
                'options' => [
                    'empty_option' => [
                        'label' => $this->translator->translate('Select an option'),
                        'selected' => 'selected',
                        'disabled' => 'disabled',
                    ],
                    'value_options' => $organOptions,
                ],
            ]
        );

        $this->add(
            [
                'name' => 'name',
                'type' => Text::class,
            ]
        );

        $this->add(
            [
                'name' => 'description',
                'type' => Text::class,
            ]
        );

        $this->add(
            [
                'name' => 'options',
                'type' => Collection::class,
                'options' => [
                    'count' => 1,
                    'should_create_template' => true,
                    'allow_add' => true,
                    'target_element' => new ActivityCalendarOption(
                        $this->translator,
                        $this->calendarFormService,
                    ),
                ],
            ]
        );
    }

    /**
     * Input filter specification.
     */
    public function getInputFilterSpecification(): array
    {
        return [
            'organ' => [
                'required' => true,
            ],
            'name' => [
                'required' => true,
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
            'description' => [
                'required' => false,
            ],
            'options' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => Callback::class,
                        'options' => [
                            'messages' => [
                                Callback::INVALID_VALUE => $this->translator->translate(
                                    'The activity does now have an acceptable amount of options',
                                ),
                            ],
                            'callback' => function ($value, $context = []) {
                                return $this->isGoodOptionCount($value, $context);
                            },
                        ],
                    ],
                    [
                        'name' => Callback::class,
                        'options' => [
                            'messages' => [
                                Callback::INVALID_VALUE => $this->translator->translate(
                                    'The options for this proposal do not fit in the valid range.',
                                ),
                            ],
                            'callback' => function ($value, $context = []) {
                                return $this->areGoodOptionDates($value, $context);
                            },
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Check if the amount of options is acceptable.
     *
     * @param array $value
     * @param array $context
     *
     * @return bool
     */
    public function isGoodOptionCount(
        array $value,
        array $context = [],
    ): bool {
        if (count($value) < 1 || count($value) > $this->maxOptions) {
            return false;
        }

        return true;
    }

    /**
     * Check if the begin times of the options are acceptable.
     *
     * @param array $value
     * @param array $context
     *
     * @return bool
     */
    public function areGoodOptionDates(
        array $value,
        array $context = [],
    ): bool {
        $final = true;
        foreach ($value as $option) {
            try {
                $beginTime = $this->calendarFormService->toDateTime($option['beginTime']);
                $result = $this->calendarFormService->canCreateOption($beginTime);
                $final = $final && $result;
            } catch (Exception $e) {
                return false;
            }
        }

        return $final;
    }
}
