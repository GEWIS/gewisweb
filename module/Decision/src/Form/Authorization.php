<?php

declare(strict_types=1);

namespace Decision\Form;

use Laminas\Form\Element\Checkbox;
use Laminas\Form\Element\Csrf;
use Laminas\Form\Element\Hidden;
use Laminas\Form\Element\Submit;
use Laminas\Form\Form;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Mvc\I18n\Translator;

/**
 * @psalm-suppress MissingTemplateParam
 */
class Authorization extends Form implements InputFilterProviderInterface
{
    public function __construct(Translator $translate)
    {
        parent::__construct();

        $this->add(
            [
                'name' => 'recipient',
                'type' => Hidden::class,
            ],
        );

        $this->add(
            [
                'name' => 'agree',
                'type' => Checkbox::class,
                'options' => [
                    'use_hidden_element' => false,
                ],
            ],
        );

        $this->add(
            [
                'name' => 'csrf_token',
                'type' => Csrf::class,
            ],
        );

        $this->add(
            [
                'name' => 'submit',
                'type' => Submit::class,
                'attributes' => [
                    'label' => $translate->translate('Authorize'),
                ],
            ],
        );
    }

    /**
     * Input filter specification.
     */
    public function getInputFilterSpecification(): array
    {
        return [
            'agree' => [
                'required' => true,
            ],
        ];
    }
}
