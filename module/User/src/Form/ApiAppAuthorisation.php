<?php

namespace User\Form;

use Laminas\Form\Element\{
    Csrf,
    Submit,
};
use Laminas\Form\Form;
use Laminas\Mvc\I18n\Translator;

class ApiAppAuthorisation extends Form
{
    private string $type;
    /**
     * @param Translator $translator
     * @param string $type
     */
    public function __construct(
        Translator $translator,
        string $type = 'initial',
    ) {
        parent::__construct();
        $this->type = $type;

        if ('initial' === $this->type) {
            $this->add(
                [
                    'name' => 'cancel',
                    'type' => Submit::class,
                    'attributes' => [
                        'value' => $translator->translate('Cancel'),
                        'class' => 'btn btn-default',
                    ],
                ]
            );

            $this->add(
                [
                    'name' => 'authorise',
                    'type' => Submit::class,
                    'attributes' => [
                        'value' => $translator->translate('Authorise'),
                        'class' => 'btn btn-primary',
                    ],
                ]
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
                ]
            );
        }

        $this->add(
            [
                'name' => 'security',
                'type' => Csrf::class,
            ]
        );
    }
}
