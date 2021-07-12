<?php

namespace Activity\Form;

use Activity\Model\SignupField;
use Activity\Model\UserSignup;
use Zend\Captcha\Image as ImageCaptcha;
use Zend\Form\Form;
use Zend\InputFilter\InputFilterProviderInterface;
use Zend\Hydrator\ClassMethods as ClassMethodsHydrator;

//input filter

class Signup extends Form implements InputFilterProviderInterface
{
    const USER = 1;
    const EXTERNAL_USER = 2;
    const EXTERNAL_ADMIN = 3;

    protected $type;
    protected $signupList;

    public function __construct()
    {
        parent::__construct('activitysignup');
        $this->setAttribute('method', 'post');
        $this->setHydrator(new ClassMethodsHydrator(false))
            ->setObject(new UserSignup());

        $this->add(
            [
            'name' => 'security',
            'type' => 'Zend\Form\Element\Csrf'
            ]
        );

        $this->add(
            [
            'name' => 'submit',
            'attributes' => [
                'type' => 'submit',
                'value' => 'Subscribe',
            ],
            ]
        );
    }

    public function getType()
    {
        return $this->type;
    }

    public function initialiseExternalForm($signupList)
    {
        $this->add(
            [
            'name' => 'captcha',
            'type' => 'Zend\Form\Element\Captcha',
            'options' => [
                'captcha' => new ImageCaptcha(
                    [
                    'font' => 'public/fonts/bitstream-vera/Vera.ttf',
                    'imgDir' => 'public/img/captcha/',
                    'imgUrl' => '/img/captcha/',
                    ]
                ),
            ]
            ]
        );
        $this->initialiseExternalAdminForm($signupList);
        $this->type = Signup::EXTERNAL_USER;
    }

    /**
     * Initialize the form for external subscriptions by admin, i.e. set the language and the fields
     * Add every field in $signupList to the form.
     *
     * @param \Activity\Model\SignupList $signupList
     */
    public function initialiseExternalAdminForm($signupList)
    {
        $this->add(
            [
            'name' => 'fullName',
            'type' => 'Text'
            ]
        );
        $this->add(
            [
            'name' => 'email',
            'type' => 'Text'
            ]
        );
        $this->initialiseForm($signupList);
        $this->type = Signup::EXTERNAL_ADMIN;
    }

    /**
     * Initialize the form, i.e. set the language and the fields
     * Add every field in $signupList to the form.
     *
     * @param \Activity\Model\SignupList $signupList
     */
    public function initialiseForm($signupList)
    {
        foreach ($signupList->getFields() as $field) {
            $this->add($this->createSignupFieldElementArray($field));
        }

        $this->signupList = $signupList;
        $this->type = Signup::USER;
    }

    /**
     * Creates an array of the form element specification for the given $field,
     * to be used by the factory.
     *
     * @param SignupField $field
     * @return array
     */
    protected function createSignupFieldElementArray($field)
    {
        $result = [
            'name' => $field->getId(),
        ];
        switch ($field->getType()) {
            case 0: //'Text'
                $result['type'] = 'Text';
                break;
            case 1: //'Yes/No'
                $result['type'] = 'Zend\Form\Element\Radio';
                $result['options'] = [
                    'value_options' => [
                        '1' => 'Yes',
                        '0' => 'No',
                    ]
                ];
                break;
            case 2: //'Number'
                $result['type'] = 'Zend\Form\Element\Number';
                $result['attributes'] = [
                    'min' => $field->getMinimumValue(),
                    'max' => $field->getMaximumValue(),
                    'step' => '1'
                ];
                break;
            case 3: //'Choice'
                $values = [];
                foreach ($field->getOptions() as $option) {
                    $values[$option->getId()] = $option->getValue()->getText();
                }
                $result['type'] = 'Zend\Form\Element\Select';
                $result['options'] = [
                    //'empty_option' => 'Make a choice',
                    'value_options' => $values
                ];
                break;
        }
        return $result;
    }

    /**
     * Apparently, validators are automatically added, so this works.
     *
     * @return type array
     */
    public function getInputFilterSpecification()
    {
        $filter = [];
        if ($this->type === Signup::EXTERNAL_USER ||
            $this->type === Signup::EXTERNAL_ADMIN) {
            $filter['fullName'] = [
                'required' => true,
                'validators' => [
                    [
                        'name' => 'StringLength',
                        'options' => [
                            'encoding' => 'UTF-8',
                            'min' => 1,
                            'max' => 100,
                        ]
                    ]
                ]
            ];
            $filter['email'] = [
                'required' => true,
                'validators' => [
                    [
                        'name' => 'StringLength',
                        'options' => [
                            'encoding' => 'UTF-8',
                            'min' => 1,
                            'max' => 100,
                        ]
                    ],
                    [
                        'name' => 'EmailAddress',
                    ],
                ],
            ];
        }

        return $filter;
    }
}
