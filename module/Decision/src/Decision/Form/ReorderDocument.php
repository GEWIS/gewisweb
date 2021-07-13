<?php

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

class ReorderDocument extends Form implements InputFilterProviderInterface
{
    /**
     * @var Translator
     */
    protected $translator;

    public function __construct($name = null, $options = [])
    {
        parent::__construct($name, $options);

        $this->setAttribute('method', 'post');
    }

    public function setupElements()
    {
        $this->add(
            [
            'type' => Radio::class,
            'name' => 'direction',
            'options' => [
                'label' => 'Label',
                'value_options' => [
                    'up' => ReorderDocument::generateIcon('fa-chevron-up', $this->translator->translate('Move up')),
                    'down' => ReorderDocument::generateIcon('fa-chevron-down', $this->translator->translate('Move down')),
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
            'type' => Hidden::class,
            'name' => 'document',
            'attributes' => [
                'value' => null, // Value should be populated in the view
            ],
            ]
        );

        return $this;
    }

    public function getInputFilterSpecification()
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

    public function setTranslator(Translator $translator)
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
     *
     * @return string
     */
    private static function generateIcon($className, $title)
    {
        return "<span class=\"fa {$className}\" title=\"{$title}\"></span>";
    }
}
