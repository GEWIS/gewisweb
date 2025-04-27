<?php

declare(strict_types=1);

namespace Decision\Form;

use Laminas\Form\Element\Submit;
use Laminas\Form\Element\Text;
use Laminas\Form\Form;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Mvc\I18n\Translator;
use Laminas\Validator\NotEmpty;

/**
 * @psalm-suppress MissingTemplateParam
 */
class SearchDecision extends Form implements InputFilterProviderInterface
{
    public function __construct(Translator $translate)
    {
        parent::__construct();

        $this->add(
            [
                'name' => 'query',
                'type' => Text::class,
                'options' => [
                    'label' => $translate->translate('Search query'),
                ],
                'attributes' => [
                    'autofocus' => true,
                ],
            ],
        );

        $this->add(
            [
                'name' => 'submit',
                'type' => Submit::class,
                'attributes' => [
                    'value' => $translate->translate('Search'),
                    'label' => $translate->translate('Search'),
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
            'query' => [
                'required' => true,
                'validators' => [
                    ['name' => NotEmpty::class],
                ],
            ],
        ];
    }
}
