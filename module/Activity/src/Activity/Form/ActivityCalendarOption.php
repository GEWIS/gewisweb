<?php

namespace Activity\Form;

use Decision\Model\Organ;
use Zend\Form\Form;
use Zend\Mvc\I18n\Translator;
use Zend\InputFilter\InputFilterProviderInterface;

class ActivityCalendarOption extends Form implements InputFilterProviderInterface
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

        // all the organs that the user belongs to in organId => name pairs
        $organOptions = [0 => $translator->translate('No organ')];

        foreach ($organs as $organ) {
            $organOptions[$organ->getId()] = $organ->getAbbr();
        }

        $this->add([
            'name' => 'name',
            'attributes' => [
                'type' => 'text',
            ],
        ]);

        $this->add([
            'name' => 'organ',
            'type' => 'select',
            'options' => [
                'value_options' => $organOptions
            ]
        ]);

        $this->add([
            'name' => 'beginTime',
            'type' => 'datetime',
            'options' => [
                'format' => 'Y/m/d H:i'
            ],
        ]);

        $this->add([
            'name' => 'endTime',
            'type' => 'datetime',
            'options' => [
                'format' => 'Y/m/d H:i'
            ],
        ]);
    }

    /**
     * Input filter specification.
     */
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
            $thisTime = new \DateTime($value);
            $endTime = isset($context['endTime']) ? new \DateTime($context['endTime']) : new \DateTime('now');
            return $thisTime <= $endTime;
        } catch (\Exception $e) {
            // An exception is an indication that one of the times was not valid
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
            $time = new \DateTime($value);
            $now = new \DateTime();
            return $time > $now;
        } catch (\Exception $e) {
            // An exception is an indication that one of the times was not valid
            return false;
        }
    }
}
