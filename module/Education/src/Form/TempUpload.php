<?php

declare(strict_types=1);

namespace Education\Form;

use Laminas\Form\Element\File;
use Laminas\Form\Form;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Mvc\I18n\Translator;
use Laminas\Validator\File\Extension;
use Laminas\Validator\File\MimeType;
use Override;

/**
 * @psalm-suppress MissingTemplateParam
 */
class TempUpload extends Form implements InputFilterProviderInterface
{
    public function __construct(Translator $translator)
    {
        parent::__construct();

        $this->add(
            [
                'name' => 'file',
                'type' => File::class,
                'options' => [
                    'label' => $translator->translate('Exam to upload'),
                ],
            ],
        );
    }

    #[Override]
    public function getInputFilterSpecification(): array
    {
        return [
            'file' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => Extension::class,
                        'options' => [
                            'extension' => 'pdf',
                        ],
                    ],
                    [
                        'name' => MimeType::class,
                        'options' => [
                            'mimeType' => 'application/pdf',
                        ],
                    ],
                ],
            ],
        ];
    }
}
