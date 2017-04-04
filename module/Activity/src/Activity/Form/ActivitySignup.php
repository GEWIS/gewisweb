<?php

namespace Activity\Form;

use Zend\Form\Form;
//input filter
use Zend\InputFilter\InputFilterInterface;
use Zend\Stdlib\Hydrator\ClassMethods as ClassMethodsHydrator;
use Zend\InputFilter\InputFilterProviderInterface;
use Zend\Captcha\Image as ImageCaptcha;

class ActivitySignup extends Form implements InputFilterProviderInterface
{
    const USER = 1;
    const EXTERNAL_USER = 2;
    const EXTERNAL_ADMIN = 3;

    protected $type;
    protected $fields;

    public function __construct()
    {
        parent::__construct('activitysignup');
        $this->setAttribute('method', 'post');
        $this->setHydrator(new ClassMethodsHydrator(false))
            ->setObject(new \Activity\Model\UserActivitySignup());

        $this->add([
            'name' => 'security',
            'type' => 'Zend\Form\Element\Csrf'
        ]);

        $this->add([
            'name' => 'submit',
            'attributes' => [
                'type' => 'submit',
                'value' => 'Subscribe',
            ],
        ]);
    }

    public function getType()
    {
        return $this->type;
    }

    /**
     * Initialize the form, i.e. set the language and the fields
     * Add every field in $fields to the form.
     *
     * @param array(ActivityField) $fields
     */
    public function initialiseForm($fields)
    {
        foreach($fields as $field) {
            $this->add($this->createFieldElementArray($field));
        }
        $this->fields = $fields;
        $this->type = ActivitySignup::USER;
    }

    /**
     * Initialize the form for external subscriptions by admin, i.e. set the language and the fields
     * Add every field in $fields to the form.
     *
     * @param array(ActivityField) $fields
     */
    public function initialiseExternalAdminForm($fields)
    {
        $this->add([
            'name' => 'fullName',
            'type' => 'Text'
        ]);
        $this->add([
            'name' => 'email',
            'type' => 'Text'
        ]);
        $this->initialiseForm($fields);
        $this->type = ActivitySignup::EXTERNAL_ADMIN;
    }

    public function initialiseExternalForm($fields)
    {
        $this->add([
            'name' => 'captcha',
            'type' => 'Zend\Form\Element\Captcha',
            'options' => [
                'captcha' => new ImageCaptcha([
                    'font' => 'public/fonts/bitstream-vera/Vera.ttf',
                    'imgDir' => 'public/img/captcha/',
                    'imgUrl' => '/img/captcha/',
                    ]),
            ]
        ]);
        $this->initialiseExternalAdminForm($fields);
        $this->type = ActivitySignup::EXTERNAL_USER;
    }

    /**
     * Apparently, validators are automatically added, so this works.
     *
     * @return type array
     */
    public function getInputFilterSpecification()
    {
        $filter = [];
        if ($this->type === ActivitySignup::EXTERNAL_USER ||
            $this->type ===  ActivitySignup::EXTERNAL_ADMIN) {
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

    public function setInputFilter(InputFilterInterface $inputFilter)
    {
        throw new \Exception('Not used');
    }

    /**
     * Creates an array of the form element specification for the given $field,
     * to be used by the factory.
     *
     * @param \Activity\Model\ActivityField $field
     * @param bool $setEnglish
     * @return array
     */
    protected function createFieldElementArray($field){

        $result = [
            'name' => $field->getId(),
        ];
        switch($field->getType()) {
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
                foreach($field->getOptions() as $option){
                    $values[$option->getId()] =  $option->getValue();
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
}
