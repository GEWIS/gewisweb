<?php

declare(strict_types=1);

namespace Decision\Form;

use Laminas\Form\Element\Checkbox;
use Laminas\Form\Element\Csrf;
use Laminas\Form\Element\Submit;
use Laminas\Form\Form;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Mvc\I18n\Translator;
use Override;

/**
 * @psalm-suppress MissingTemplateParam
 */
class AuthorizationRevocation extends Form implements InputFilterProviderInterface
{
    public function __construct(Translator $translate)
    {
        parent::__construct();

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
                    'value' => $translate->translate('Revoke Authorization'),
                ],
            ],
        );
    }

    /**
     * Input filter specification.
     */
    #[Override]
    public function getInputFilterSpecification(): array
    {
        return [
            'agree' => [
                'required' => true,
            ],
        ];
    }
}
