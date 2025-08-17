<?php

declare(strict_types=1);

namespace Activity\Form;

use Application\Form\Localisable as LocalisableForm;
use Laminas\Form\Element\Submit;
use Laminas\Form\Element\Text;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Mvc\I18n\Translator;
use Override;

class ActivityCategory extends LocalisableForm implements InputFilterProviderInterface
{
    public function __construct(Translator $translator)
    {
        parent::__construct($translator);

        $this->add(
            [
                'name' => 'name',
                'type' => Text::class,
                'options' => [
                    'label' => $this->getTranslator()->translate('Name'),
                ],
            ],
        );

        $this->add(
            [
                'name' => 'nameEn',
                'type' => Text::class,
                'options' => [
                    'label' => $this->getTranslator()->translate('Name'),
                ],
            ],
        );

        $this->add(
            [
                'name' => 'submit',
                'type' => Submit::class,
                'attributes' => [
                    'value' => $this->getTranslator()->translate('Create Activity Category'),
                ],
            ],
        );
    }

    /**
     * @inheritDoc
     */
    #[Override]
    protected function createLocalisedInputFilterSpecification(string $suffix = ''): array
    {
        return [
            'name' . $suffix => [
                'required' => true,
                'validators' => [
                    [
                        'name' => 'StringLength',
                        'options' => [
                            'encoding' => 'UTF-8',
                            'min' => 1,
                            'max' => 100,
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Validate the form.
     */
    #[Override]
    public function isValid(): bool
    {
        $valid = parent::isValid();
        $this->isValid = $valid;

        return $valid;
    }
}
