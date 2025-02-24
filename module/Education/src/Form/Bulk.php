<?php

declare(strict_types=1);

namespace Education\Form;

use Education\Mapper\Course as CourseMapper;
use Laminas\Form\Element\Collection;
use Laminas\Form\Element\Submit;
use Laminas\Form\Fieldset;
use Laminas\Form\Form;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Mvc\I18n\Translator;
use Laminas\Validator\NotEmpty;

/**
 * @psalm-suppress MissingTemplateParam
 */
class Bulk extends Form implements InputFilterProviderInterface
{
    public function __construct(
        private readonly Translator $translator,
        private readonly CourseMapper $courseMapper,
        Fieldset $document,
    ) {
        parent::__construct();

        $this->add(
            [
                'name' => 'documents',
                'type' => Collection::class,
                'options' => [
                    'count' => 0,
                    'allow_add' => true,
                    'allow_remove' => true,
                    'target_element' => $document,
                ],
            ],
        );

        $this->add(
            [
                'name' => 'submit',
                'type' => Submit::class,
                'attributes' => [
                    'value' => $this->translator->translate('Finalize uploads'),
                ],
            ],
        );
    }

    public function isValid(): bool
    {
        $valid = parent::isValid();

        foreach ($this->get('documents')->getFieldSets() as $document) {
            if (!(new NotEmpty())->isValid($document->get('course')->getValue())) {
                $document->get('course')->setMessages(
                    [
                        $this->translator->translate('Value is required and can\'t be empty'),
                    ],
                );
                $valid = false;
            } elseif (null === $this->courseMapper->findByCode($document->get('course')->getValue())) {
                $document->get('course')->setMessages(
                    [
                        $this->translator->translate('Course does not exist'),
                    ],
                );
                $valid = false;
            }
        }

        return $valid;
    }

    public function getInputFilterSpecification(): array
    {
        return [];
    }
}
