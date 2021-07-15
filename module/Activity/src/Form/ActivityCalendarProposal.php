<?php

namespace Activity\Form;

use Activity\Service\ActivityCalendarForm;
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
     * @var ActivityCalendarForm
     */
    private $calendarFormService;

    /**
     * @var int
     */
    private $maxOptions;

    /**
     * @param Translator $translator
     * @param ActivityCalendarForm $calendarFormService
     * @param bool $createAlways
     */
    public function __construct(Translator $translator, ActivityCalendarForm $calendarFormService, bool $createAlways)
    {
        parent::__construct();
        $this->translator = $translator;
        $this->calendarFormService = $calendarFormService;

        $organs = $calendarFormService->getEditableOrgans();
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
                    'target_element' => new ActivityCalendarOption($translator, $calendarFormService),
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
     * @param int $value
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
     * @param array $value
     * @param array $context
     *
     * @return bool
     */
    public function areGoodOptionDates($value, $context = [])
    {
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
