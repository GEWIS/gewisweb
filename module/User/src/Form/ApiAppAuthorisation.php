<?php

declare(strict_types=1);

namespace User\Form;

use Laminas\Form\Element\Csrf;
use Laminas\Form\Element\Submit;
use Laminas\Form\Form;
use Laminas\Mvc\I18n\Translator;

/**
 * @psalm-suppress MissingTemplateParam
 */
class ApiAppAuthorisation extends Form
{
    public function __construct(
        Translator $translator,
        private readonly string $type = 'initial',
    ) {
        parent::__construct();

        if ('initial' === $this->type) {
            $this->add(
                [
                    'name' => 'cancel',
                    'type' => Submit::class,
                    'attributes' => [
                        'value' => $translator->translate('Cancel'),
                        'class' => 'btn btn-default',
                    ],
                ],
            );

            $this->add(
                [
                    'name' => 'authorise',
                    'type' => Submit::class,
                    'attributes' => [
                        'value' => $translator->translate('Authorise'),
                        'class' => 'btn btn-primary',
                    ],
                ],
            );
        } else {
            $this->add(
                [
                    'name' => 'continue',
                    'type' => Submit::class,
                    'attributes' => [
                        'value' => $translator->translate('Continue'),
                        'class' => 'btn btn-primary',
                    ],
                ],
            );
        }

        $this->add(
            [
                'name' => 'security',
                'type' => Csrf::class,
            ],
        );
    }
}
