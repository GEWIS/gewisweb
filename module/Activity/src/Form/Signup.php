<?php

declare(strict_types=1);

namespace Activity\Form;

use Activity\Form\Element\ValidatedText;
use Activity\Model\SignupField as SignupFieldModel;
use Activity\Model\SignupList as SignupListModel;
use Laminas\Captcha\Image as ImageCaptcha;
use Laminas\Form\Element\Captcha;
use Laminas\Form\Element\Csrf;
use Laminas\Form\Element\Email;
use Laminas\Form\Element\Number;
use Laminas\Form\Element\Radio;
use Laminas\Form\Element\Select;
use Laminas\Form\Element\Submit;
use Laminas\Form\Element\Text;
use Laminas\Form\Form;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Validator\EmailAddress;
use Laminas\Validator\StringLength;

use function strval;

class Signup extends Form implements InputFilterProviderInterface
{
    public const USER = 1;
    public const EXTERNAL_USER = 2;
    public const EXTERNAL_ADMIN = 3;

    protected int $type;

    protected SignupListModel $signupList;

    public function __construct()
    {
        parent::__construct('activitysignup');

        $this->setAttribute('method', 'post');

        $this->add(
            [
                'name' => 'security',
                'type' => Csrf::class,
            ],
        );

        $this->add(
            [
                'name' => 'submit',
                'type' => Submit::class,
                'attributes' => [
                    'value' => 'Subscribe',
                ],
            ],
        );
    }

    public function getType(): int
    {
        return $this->type;
    }

    public function initialiseExternalForm(SignupListModel $signupList): void
    {
        $this->add(
            [
                'name' => 'captcha',
                'type' => Captcha::class,
                'options' => [
                    'captcha' => new ImageCaptcha(
                        [
                            'font' => 'public/fonts/bitstream-vera/Vera.ttf',
                            'imgDir' => 'public/img/captcha/',
                            'imgUrl' => '/img/captcha/',
                        ],
                    ),
                ],
            ],
        );

        $this->initialiseExternalAdminForm($signupList);
        $this->type = self::EXTERNAL_USER;
    }

    /**
     * Initialize the form for external subscriptions by admin, i.e. set the language and the fields
     * Add every field in $signupList to the form.
     */
    public function initialiseExternalAdminForm(SignupListModel $signupList): void
    {
        $this->add(
            [
                'name' => 'fullName',
                'type' => Text::class,
            ],
        );

        $this->add(
            [
                'name' => 'email',
                'type' => Email::class,
            ],
        );

        $this->initialiseForm($signupList);
        $this->type = self::EXTERNAL_ADMIN;
    }

    /**
     * Initialize the form, i.e. set the language and the fields
     * Add every field in $signupList to the form.
     */
    public function initialiseForm(SignupListModel $signupList): void
    {
        foreach ($signupList->getFields() as $field) {
            $this->add($this->createSignupFieldElementArray($field));
        }

        $this->signupList = $signupList;
        $this->type = self::USER;
    }

    /**
     * Creates an array of the form element specification for the given $field,
     * to be used by the factory.
     */
    protected function createSignupFieldElementArray(SignupFieldModel $field): array
    {
        $result = [
            'name' => strval($field->getId()),
        ];

        switch ($field->getType()) {
            case 0: //'Text'
                $result['type'] = ValidatedText::class;
                break;
            case 1: //'Yes/No'
                $result['type'] = Radio::class;
                $result['options'] = [
                    'value_options' => [
                        '1' => 'Yes',
                        '0' => 'No',
                    ],
                ];
                break;
            case 2: //'Number'
                $result['type'] = Number::class;
                $result['attributes'] = [
                    'min' => $field->getMinimumValue(),
                    'max' => $field->getMaximumValue(),
                    'step' => '1',
                ];
                break;
            case 3: //'Choice'
                $values = [];
                foreach ($field->getOptions() as $option) {
                    $values[$option->getId()] = $option->getValue()->getText();
                }

                $result['type'] = Select::class;
                $result['options'] = [
                    //'empty_option' => 'Make a choice',
                    'value_options' => $values,
                ];
                break;
        }

        return $result;
    }

    /**
     * Apparently, validators are automatically added, so this works.
     */
    public function getInputFilterSpecification(): array
    {
        $filter = [];
        if (
            self::EXTERNAL_USER === $this->type ||
            self::EXTERNAL_ADMIN === $this->type
        ) {
            $filter['fullName'] = [
                'required' => true,
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
            ];
            $filter['email'] = [
                'required' => true,
                'validators' => [
                    [
                        'name' => StringLength::class,
                        'options' => [
                            'encoding' => 'UTF-8',
                            'min' => 1,
                            'max' => 100,
                        ],
                    ],
                    [
                        'name' => EmailAddress::class,
                    ],
                ],
            ];
        }

        return $filter;
    }
}
