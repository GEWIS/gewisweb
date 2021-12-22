<?php

namespace Decision\Form;

use Laminas\Filter\{
    ToInt,
    ToNull,
};
use Laminas\Form\Element\{
    Hidden,
    Radio,
};
use Laminas\Form\Form;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Mvc\I18n\Translator;
use Laminas\Validator\{
    InArray,
    NotEmpty,
};

class ReorderDocument extends Form implements InputFilterProviderInterface
{
    /**
     * @var Translator
     */
    protected Translator $translator;

    /**
     * @param string|null $name
     * @param array $options
     */
    public function __construct(
        ?string $name = null,
        array $options = [],
    ) {
        parent::__construct($name, $options);

        $this->setAttribute('method', 'post');
    }

    /**
     * @return self
     */
    public function setupElements(): self
    {
        $this->add(
            [
                'name' => 'direction',
                'type' => Radio::class,
                'options' => [
                    'label' => 'Label',
                    'value_options' => [
                        'up' => ReorderDocument::generateIcon(
                            'fa-chevron-up',
                            $this->translator->translate('Move up')
                        ),
                        'down' => ReorderDocument::generateIcon(
                            'fa-chevron-down',
                            $this->translator->translate('Move down')
                        ),
                    ],
                    'label_attributes' => [
                        'class' => 'label label-radio-hidden',
                    ],
                    'label_options' => [
                        'disable_html_escape' => true, // Required to render HTML icons
                    ],
                ],
            ]
        );

        $this->add(
            [
                'name' => 'document',
                'type' => Hidden::class,
                'attributes' => [
                    'value' => null, // Value should be populated in the view
                ],
            ]
        );

        return $this;
    }

    /**
     * @return array
     */
    public function getInputFilterSpecification(): array
    {
        return [
            'direction' => [
                'required' => true,
                'validators' => [
                    (new InArray())->setHaystack(['up', 'down']),
                ],
            ],
            'document' => [
                'required' => true,
                'filters' => [
                    ['name' => ToNull::class],
                    ['name' => ToInt::class],
                ],
                'validators' => [
                    ['name' => NotEmpty::class],
                ],
            ],
        ];
    }

    /**
     * @param Translator $translator
     *
     * @return self
     */
    public function setTranslator(Translator $translator): self
    {
        $this->translator = $translator;

        return $this;
    }

    /**
     * Returns an icon as a HTML string.
     *
     * FUTURE: Think of a better way to show icons in the label. Icons are
     * layout and shouldn't be defined in the Form.
     *
     * @param string $className FontAwesome class
     * @param string $title Element title
     *
     * @return string
     */
    private static function generateIcon(
        string $className,
        string $title,
    ): string {
        return "<span class=\"fa {$className}\" title=\"{$title}\"></span>";
    }
}
