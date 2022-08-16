<?php

namespace Activity\Form;

use Activity\Model\SignupList as SignupListModel;
use DateTime;
use Exception;
use Laminas\Form\Element\{
    Checkbox,
    Collection,
    DateTimeLocal,
    Text,
};
use Laminas\Form\Fieldset;
use Laminas\Hydrator\ClassMethodsHydrator;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Mvc\I18n\Translator;
use Laminas\Validator\{
    Callback,
    StringLength,
};

class SignupList extends Fieldset implements InputFilterProviderInterface
{
    public function __construct(private readonly Translator $translator)
    {
        parent::__construct('signuplist');
        $this->setHydrator(new ClassMethodsHydrator(false))
            ->setObject(new SignupListModel());

        $this->add(
            [
                'name' => 'name',
                'type' => Text::class,
                'options' => [
                    'label' => $this->translator->translate('Name'),
                ],
            ]
        );

        $this->add(
            [
                'name' => 'nameEn',
                'type' => Text::class,
                'options' => [
                    'label' => $this->translator->translate('Name'),
                ],
            ]
        );

        $this->add(
            [
                'name' => 'openDate',
                'type' => DateTimeLocal::class,
                'options' => [
                    'format' => 'Y-m-d\TH:i',
                ],
            ]
        );

        $this->add(
            [
                'name' => 'closeDate',
                'type' => DateTimeLocal::class,
                'options' => [
                    'format' => 'Y-m-d\TH:i',
                ],
            ]
        );

        $this->add(
            [
                'name' => 'onlyGEWIS',
                'type' => Checkbox::class,
                'options' => [
                    'checked_value' => '1',
                    'unchecked_value' => '0',
                ],
                'attributes' => [
                    'value' => 1,
                ],
            ]
        );

        $this->add(
            [
                'name' => 'displaySubscribedNumber',
                'type' => Checkbox::class,
                'options' => [
                    'checked_value' => '1',
                    'unchecked_value' => '0',
                ],
            ]
        );

        $this->add(
            [
                'name' => 'fields',
                'type' => Collection::class,
                'options' => [
                    'count' => 0,
                    'should_create_template' => true,
                    'template_placeholder' => '__signuplist_field__',
                    'allow_add' => true,
                    'target_element' => new SignupListField($this->translator),
                ],
            ]
        );
    }

    /**
     * Check if a certain date is before the closing date of the SignupList.
     *
     * @param string $value
     * @param array $context
     *
     * @return bool
     */
    public static function beforeCloseDate(
        string $value,
        array $context = [],
    ): bool {
        try {
            $closeTime = isset($context['closeDate']) ? new DateTime($context['closeDate']) : new DateTime('now');

            return new DateTime($value) < $closeTime;
        } catch (Exception $e) {
            // An exception is an indication that one of the times was not valid
            return false;
        }
    }

    /**
     * @return array
     */
    public function getInputFilterSpecification(): array
    {
        return [
            'name' => [
                'required' => false,
                'validators' => [
                    [
                        'name' => StringLength::class,
                        'options' => [
                            'encoding' => 'UTF-8',
                            'min' => 1,
                            'max' => 100,
                        ],
                    ],
                ],
            ],
            'nameEn' => [
                'required' => false,
                'validators' => [
                    [
                        'name' => StringLength::class,
                        'options' => [
                            'encoding' => 'UTF-8',
                            'min' => 1,
                            'max' => 100,
                        ],
                    ],
                ],
            ],
            'openDate' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => Callback::class,
                        'options' => [
                            'messages' => [
                                Callback::INVALID_VALUE => $this->translator->translate(
                                    'The sign-up list opening date and time must be before the sign-up list closes.'
                                ),
                            ],
                            'callback' => [$this, 'beforeCloseDate'],
                        ],
                    ],
                ],
            ],
            'closeDate' => [
                'required' => true,
            ],
        ];
    }
}
