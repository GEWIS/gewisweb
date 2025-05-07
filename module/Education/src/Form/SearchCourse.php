<?php

declare(strict_types=1);

namespace Education\Form;

use Laminas\Form\Form;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Mvc\I18n\Translator;
use Laminas\Validator\NotEmpty;
use Override;

/**
 * @psalm-suppress MissingTemplateParam
 */
class SearchCourse extends Form implements InputFilterProviderInterface
{
    public function __construct(Translator $translate)
    {
        parent::__construct();

        $this->add(
            [
                'name' => 'query',
                'type' => 'text',
                'options' => [
                    'label' => $translate->translate('Search query'),
                ],
            ],
        );
    }

    #[Override]
    public function getInputFilterSpecification(): array
    {
        return [
            'query' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => NotEmpty::class,
                    ],
                ],
            ],
        ];
    }
}
