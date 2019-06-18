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
     */
    public function __construct(Translator $translator)
    {
        parent::__construct();
        $this->translator = $translator;

        $typeOptions = [];

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
                'empty_option' => $translator->translate('Please select a type'),
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
                            'callback' => ['Activity\Form\ActivityCalendarOption', 'beforeEndTime']
                        ],
                    ],
                    [
                        'name' => 'callback',
                        'options' => [
                            'messages' => [
                                \Zend\Validator\Callback::INVALID_VALUE =>
                                    $this->translator->translate('The activity must after today'),
                            ],
                            'callback' => ['Activity\Form\ActivityCalendarOption', 'isFutureTime']
                        ],
                    ],
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
            $endTime = isset($context['endTime']) ? new \DateTime($context['endTime']) : new \DateTime('now');

            return (new \DateTime($value)) <= $endTime;
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

            return (new \DateTime($value)) > $today;
        } catch (\Exception $e) {
            return false;
        }
    }
}