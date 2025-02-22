<?php

declare(strict_types=1);

namespace Application\Form;

use Laminas\Form\Element\Checkbox;
use Laminas\Form\Form;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Mvc\I18n\Translator;

/**
 * A form which provides the basic structure for forms that utilise a model's {@link \Application\Model\LocalisedText}.
 *
 * @psalm-suppress MissingTemplateParam
 */
abstract class Localisable extends Form implements InputFilterProviderInterface
{
    public function __construct(
        private readonly Translator $translator,
        bool $addElements = true,
    ) {
        parent::__construct();

        if (!$addElements) {
            return;
        }

        $this->add(
            [
                'name' => 'language_dutch',
                'type' => Checkbox::class,
                'options' => [
                    'label' => $this->getTranslator()->translate('Enable Dutch Translations'),
                    'checked_value' => '1',
                    'unchecked_value' => '0',
                ],
            ],
        );

        $this->add(
            [
                'name' => 'language_english',
                'type' => Checkbox::class,
                'options' => [
                    'label' => $this->getTranslator()->translate('Enable English Translations'),
                    'checked_value' => '1',
                    'unchecked_value' => '0',
                ],
            ],
        );
    }

    public function getInputFilterSpecification(): array
    {
        $filter = [];

        if (
            isset($this->data['language_english'])
            && $this->data['language_english']
        ) {
            $filter += $this->createLocalisedInputFilterSpecification('En');
        }

        if (
            isset($this->data['language_dutch'])
            && $this->data['language_dutch']
        ) {
            $filter += $this->createLocalisedInputFilterSpecification();
        }

        // One of the language_dutch or language_english needs to set. If not, display a message at both, indicating
        // that they need to be set
        if (
            (isset($this->data['language_dutch']) && !$this->data['language_dutch'])
            && (isset($this->data['language_english']) && !$this->data['language_english'])
        ) {
            unset($this->data['language_dutch'], $this->data['language_english']);

            $filter += [
                'language_dutch' => [
                    'required' => true,
                ],
                'language_english' => [
                    'required' => true,
                ],
            ];
        }

        return $filter;
    }

    /**
     * Create an {@link \Laminas\InputFilter\InputFilter} for a specific language.
     *
     * @param string $suffix For languages that are not Dutch, a suffix should be specified (English: 'En').
     */
    abstract protected function createLocalisedInputFilterSpecification(string $suffix = ''): array;

    public function getTranslator(): Translator
    {
        return $this->translator;
    }
}
