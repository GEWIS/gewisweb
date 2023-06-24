<?php

declare(strict_types=1);

namespace Decision\Form;

use Laminas\Filter\ToInt;
use Laminas\Filter\ToNull;
use Laminas\Form\Element\Hidden;
use Laminas\Form\Element\Radio;
use Laminas\Form\Form;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Mvc\I18n\Translator;
use Laminas\Validator\InArray;
use Laminas\Validator\NotEmpty;

use function sprintf;

class ReorderDocument extends Form implements InputFilterProviderInterface
{
    protected Translator $translator;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    public function __construct(
        ?string $name = null,
        array $options = [],
    ) {
        parent::__construct($name, $options);

        $this->setAttribute('method', 'post');
    }

    public function setupElements(): self
    {
        $this->add(
            [
                'name' => 'direction',
                'type' => Radio::class,
                'options' => [
                    'label' => 'Label',
                    'value_options' => [
                        'up' => self::generateIcon(
                            'fa-chevron-up',
                            $this->translator->translate('Move up'),
                        ),
                        'down' => self::generateIcon(
                            'fa-chevron-down',
                            $this->translator->translate('Move down'),
                        ),
                    ],
                    'label_attributes' => [
                        'class' => 'label label-radio-hidden',
                    ],
                    'label_options' => [
                        'disable_html_escape' => true, // Required to render HTML icons
                    ],
                ],
            ],
        );

        $this->add(
            [
                'name' => 'document',
                'type' => Hidden::class,
                'attributes' => [
                    'value' => null, // Value should be populated in the view
                ],
            ],
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
     * @param string $title     Element title
     */
    private static function generateIcon(
        string $className,
        string $title,
    ): string {
        return sprintf(
            '<span class="fa %s" title="%s"></span>',
            $className,
            $title,
        );
    }
}
