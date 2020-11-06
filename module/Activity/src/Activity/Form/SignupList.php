<?php

namespace Activity\Form;

use Zend\Form\Fieldset;
use Zend\InputFilter\InputFilterProviderInterface;
use Zend\Mvc\I18n\Translator;
use Zend\Stdlib\Hydrator\ClassMethods as ClassMethodsHydrator;

class SignupList extends Fieldset implements InputFilterProviderInterface
{
    /**
     * @var \Zend\Mvc\I18n\Translator
     */
    protected $translator;

    public function __construct(Translator $translator)
    {
        parent::__construct('signuplist');
        $this->translator = $translator;
        $this->setHydrator(new ClassMethodsHydrator(false))
              ->setObject(new \Activity\Model\SignupList());

        $this->add([
            'name' => 'name',
            'attributes' => [
                'type' => 'text',
            ],
            'options' => [
                'label' => $translator->translate('Name'),
            ],
        ]);

        $this->add([
            'name' => 'nameEn',
            'attributes' => [
                'type' => 'text',
            ],
            'options' => [
                'label' => $translator->translate('Name'),
            ],
        ]);

        $this->add([
            'name' => 'openDate',
            'type' => 'datetime',
            'options' => [
                'format' => 'Y/m/d H:i'
            ],
        ]);

        $this->add([
            'name' => 'closeDate',
            'type' => 'datetime',
            'options' => [
                'format' => 'Y/m/d H:i'
            ],
        ]);

        $this->add([
            'name' => 'onlyGEWIS',
            'type' => 'Zend\Form\Element\Checkbox',
            'options' => [
                'checked_value' => 0,
                'unchecked_value' => 1,
                'use_hidden_element' => true,
            ],
        ]);

        $this->add([
            'name' => 'displaySubscribedNumber',
            'type' => 'Zend\Form\Element\Checkbox',
            'options' => [
                'checked_value' => 1,
                'unchecked_value' => 0,
            ],
        ]);

        $this->add([
            'name' => 'fields',
            'type' => 'Zend\Form\Element\Collection',
            'options' => [
                'count' => 0,
                'should_create_template' => true,
                'template_placeholder' => '__signuplist_field__',
                'allow_add' => true,
                'target_element' => new SignupListField($translator),
            ],
        ]);
    }

    /**
     * @return array
     */
    public function getInputFilterSpecification()
    {
        return [
            'name' => [
                'required' => false,
                'validators' => [
                    [
                        'name' => 'StringLength',
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
                        'name' => 'StringLength',
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
                // TODO: Move to an actual InputFilter
                // The validator below does not work, as the $context in
                // Activity\Form\Activity::beforeBeginTime is the context
                // of this FieldSet and not the parent form.
                //
                // This means that the `beginTime`-index does not exist and
                // as a result any `closeDate` in the future does not validate
                // correctly.
                //
                // An separate InputFilter should be made for the parent form
                // to validate any and all child forms.
                //
                'validators' => [
                    //[
                    //    'name' => 'callback',
                    //    'options' => [
                    //        'messages' => [
                    //            \Zend\Validator\Callback::INVALID_VALUE =>
                    //                $this->translator->translate('The signup list opening date and time must be before the activity starts.'),
                    //        ],
                    //        'callback' => ['Activity\Form\Activity', 'beforeBeginTime'],
                    //    ],
                    //],
                    [
                        'name' => 'callback',
                        'options' => [
                            'messages' => [
                                \Zend\Validator\Callback::INVALID_VALUE =>
                                    $this->translator->translate(
                                        'The signup list opening date and time must be before the activity closes.'
                                    ),
                            ],
                            'callback' => [$this, 'beforeCloseDate'],
                        ],
                    ],
                ],
            ],
            'closeDate' => [
                'required' => true,
                // TODO: Move to an actual InputFilter
                // The validator below does not work, as the $context in
                // Activity\Form\Activity::beforeBeginTime is the context
                // of this FieldSet and not the parent form.
                //
                // This means that the `beginTime`-index does not exist and
                // as a result any `closeDate` in the future does not validate
                // correctly.
                //
                // An separate InputFilter should be made for the parent form
                // to validate any and all child forms.
                //
                //'validators' => [
                //    [
                //        'name' => 'callback',
                //        'options' => [
                //            'messages' => [
                //                \Zend\Validator\Callback::INVALID_VALUE =>
                //                    $this->translator->translate('The signup list closing date and time must be before the activity starts.'),
                //            ],
                //            'callback' => ['Activity\Form\Activity', 'beforeBeginTime'],
                //        ],
                //    ],
                //],
            ],
        ];
    }

    /**
     * Validate the form
     *
     * @return bool
     * @throws Exception\DomainException
     */
    public function isValid()
    {
        $valid = parent::isValid();
        $this->isValid = $valid;

        return $valid;
    }

    /**
     * Check if a certain date is before the closing date of the SignupList.
     *
     * @param $value
     * @param array $context
     * @return bool
     */
    public static function beforeCloseDate($value, $context = [])
    {
        try {
            $thisTime = new \DateTime($value);
            $closeTime = isset($context['closeDate']) ? new \DateTime($context['closeDate']) : new \DateTime('now');
            return $thisTime < $closeTime;
        } catch (\Exception $e) {
            // An exception is an indication that one of the times was not valid
            return false;
        }
    }
}