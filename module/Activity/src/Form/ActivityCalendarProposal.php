<?php

declare(strict_types=1);

namespace Activity\Form;

use Activity\Service\ActivityCalendarForm as ActivityCalendarFormService;
use Laminas\Form\Element\Collection;
use Laminas\Form\Element\Select;
use Laminas\Form\Element\Text;
use Laminas\Form\Form;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Mvc\I18n\Translator;
use Laminas\Validator\Callback;
use Laminas\Validator\NotEmpty;
use Laminas\Validator\StringLength;
use User\Permissions\NotAllowedException;

use function count;

/**
 * @psalm-suppress MissingTemplateParam
 */
class ActivityCalendarProposal extends Form implements InputFilterProviderInterface
{
    private int $maxOptions;

    public function __construct(
        private readonly Translator $translator,
        private readonly ActivityCalendarFormService $calendarFormService,
        private readonly bool $createAlways,
    ) {
        parent::__construct();

        try {
            $organs = $this->calendarFormService->getEditableOrgans();
        } catch (NotAllowedException) {
            $organs = [];
        }

        $organOptions = [];
        foreach ($organs as $organ) {
            $organOptions[$organ->getId()] = $organ->getAbbr();
        }

        $periodOptions = [];
        foreach ($this->calendarFormService->getCurrentPeriods() as $period) {
            $periodOptions[$period->getId()] = $period->getBeginOptionTime()->format('Y-m-d')
                . ' - ' . $period->getEndOptionTime()->format('Y-m-d');
        }

        if ($this->createAlways) {
            $organOptions[-1] = 'Board';
            $organOptions[-2] = 'Other';
            $periodOptions[-1] = 'Board';
        }

        $this->maxOptions = 3;

        $this->add(
            [
                'name' => 'period',
                'type' => Select::class,
                'options' => [
                    'empty_option' => [
                        'label' => $this->translator->translate('Select a period'),
                        'selected' => 'selected',
                        'disabled' => 'disabled',
                    ],
                    'value_options' => $periodOptions,
                ],
            ],
        );

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
            ],
        );

        $this->add(
            [
                'name' => 'name',
                'type' => Text::class,
            ],
        );

        $this->add(
            [
                'name' => 'description',
                'type' => Text::class,
            ],
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
            ],
        );
    }

    /**
     * Validate the form.
     */
    public function isValid(): bool
    {
        $valid = parent::isValid();

        foreach ($this->get('options')->getFieldSets() as $option) {
            if (!(new NotEmpty())->isValid($option->get('type')->getValue())) {
                $option->get('type')->setMessages([
                    $this->translator->translate('Value is required and can\'t be empty'),
                ]);
                $valid = false;
            }

            if (!(null !== ($period = $this->data['period'] ?? null))) {
                continue;
            }

            $period = (int) $period;

            $missingDate = false;
            if (!(new NotEmpty())->isValid($option->get('beginTime')->getValue())) {
                $option->get('beginTime')->setMessages([
                    $this->translator->translate('Value is required and can\'t be empty'),
                ]);
                $valid = false;
                $missingDate = true;
            }

            if (!(new NotEmpty())->isValid($option->get('endTime')->getValue())) {
                $option->get('endTime')->setMessages([
                    $this->translator->translate('Value is required and can\'t be empty'),
                ]);
                $valid = false;
                $missingDate = true;
            }

            if ($missingDate) {
                continue;
            }

            $beginTime = $this->calendarFormService->toDateTime($option->get('beginTime')->getValue());
            $endTime = $this->calendarFormService->toDateTime($option->get('endTime')->getValue());

            if ($endTime < $beginTime) {
                $option->get('endTime')->setMessages([
                    $this->translator->translate('Option should end after it starts.'),
                ]);
                $valid = false;
            }

            if ($this->calendarFormService->canCreateOptionInPeriod($period, $beginTime, $endTime)) {
                continue;
            }

            $option->get('beginTime')->setMessages([
                $this->translator->translate(
                    'Option does not fall within option period (also check the end date).',
                ),
            ]);
            $valid = false;
        }

        return $valid;
    }

    /**
     * Input filter specification.
     */
    public function getInputFilterSpecification(): array
    {
        return [
            'period' => [
                'required' => true,
            ],
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
                            'callback' => function ($value) {
                                return $this->isGoodOptionCount($value);
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
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    public function isGoodOptionCount(array $value): bool
    {
        return count($value) >= 1 && count($value) <= $this->maxOptions;
    }
}
