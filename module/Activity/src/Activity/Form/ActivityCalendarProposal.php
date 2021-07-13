<?php

namespace Activity\Form;

use Activity\Service\ActivityCalendar;
use Exception;
use Laminas\Form\Form;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Mvc\I18n\Translator;
use Laminas\Validator\Callback;
use Laminas\Validator\StringLength;

class ActivityCalendarProposal extends Form implements InputFilterProviderInterface
{
    protected $translator;

    /**
     * @var ActivityCalendar
     */
    private $calendarService;

    /**
     * @var int
     */
    private $maxOptions;

    /**
     * @param ActivityCalendar $calendarService
     *
     * @throws Exception
     */
    public function __construct(Translator $translator, $calendarService)
    {
        parent::__construct();
        $this->translator = $translator;
        $this->calendarService = $calendarService;

        $organs = $calendarService->getEditableOrgans();
        $organOptions = [];
        foreach ($organs as $organ) {
            $organOptions[$organ->getId()] = $organ->getAbbr();
        }
        if ($calendarService->isAllowed('create_always')) {
            $organOptions[-1] = 'Board';
            $organOptions[-2] = 'Other';
        }

        $this->maxOptions = 3;

        $this->add(
            [
            'name' => 'organ',
            'type' => 'select',
            'options' => [
                'empty_option' => [
                    'label' => $translator->translate('Select an option'),
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
            'attributes' => [
                'type' => 'text',
            ],
            ]
        );

        $this->add(
            [
            'name' => 'description',
            'attributes' => [
                'type' => 'text',
            ],
            ]
        );

        $this->add(
            [
            'name' => 'options',
            'type' => 'Laminas\Form\Element\Collection',
            'options' => [
                'count' => 1,
                'should_create_template' => true,
                'allow_add' => true,
                'target_element' => new ActivityCalendarOption($translator, $calendarService),
            ],
            ]
        );
    }

    /**
     * Input filter specification.
     */
    public function getInputFilterSpecification()
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
                                Callback::INVALID_VALUE => $this->translator->translate('The activity does now have an acceptable amount of options'),
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
                                Callback::INVALID_VALUE => $this->translator->translate('The options for this proposal do not fit in the valid range.'),
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
     * @param $value
     * @param array $context
     *
     * @return bool
     */
    public function isGoodOptionCount($value, $context = [])
    {
        if (count($value) < 1) {
            return false;
        }
        if (count($value) > $this->maxOptions) {
            return false;
        }

        return true;
    }

    /**
     * Check if the begin times of the options are acceptable.
     *
     * @param $value
     * @param array $context
     *
     * @return bool
     */
    public function areGoodOptionDates($value, $context = [])
    {
        $final = true;
        foreach ($value as $option) {
            try {
                $beginTime = $this->calendarService->toDateTime($option['beginTime']);
                $result = $this->calendarService->canCreateOption($beginTime);
                $final = $final && $result;
            } catch (Exception $e) {
                return false;
            }
        }

        return $final;
    }
}
