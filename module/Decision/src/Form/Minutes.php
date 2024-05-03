<?php

declare(strict_types=1);

namespace Decision\Form;

use Decision\Mapper\Meeting as MeetingMapper;
use Laminas\Form\Element\File;
use Laminas\Form\Element\Select;
use Laminas\Form\Element\Submit;
use Laminas\Form\Form;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Mvc\I18n\Translator;
use Laminas\Validator\File\Extension;
use Laminas\Validator\File\MimeType;

/**
 * @psalm-import-type MeetingArrayType from MeetingMapper as ImportedMeetingArrayType
 */
class Minutes extends Form implements InputFilterProviderInterface
{
    public const ERROR_FILE_EXISTS = 'file_exists';

    public function __construct(private readonly Translator $translator)
    {
        parent::__construct();

        $this->add(
            [
                'name' => 'meeting',
                'type' => Select::class,
                'options' => [
                    'label' => $this->translator->translate('Meeting'),
                    'empty_option' => $this->translator->translate('Choose a meeting'),
                    'value_options' => [],
                ],
            ],
        );

        $this->add(
            [
                'name' => 'upload',
                'type' => File::class,
                'option' => [
                    'label' => $translator->translate('Minutes to upload'),
                ],
            ],
        );

        $this->add(
            [
                'name' => 'submit',
                'type' => Submit::class,
                'attributes' => [
                    'value' => $translator->translate('Submit'),
                ],
            ],
        );
    }

    /**
     * @psalm-param ImportedMeetingArrayType $meetings
     *
     * @return Minutes
     */
    public function setMeetings(array $meetings): self
    {
        $options = [];
        foreach ($meetings as $meeting) {
            $meeting = $meeting[0];
            $name = $meeting->getType()->value . '/' . $meeting->getNumber();
            $options[$name] = $meeting->getType()->value . ' ' . $meeting->getNumber()
                . ' (' . $meeting->getDate()->format('Y-m-d') . ')';
        }

        $this->get('meeting')->setValueOptions($options);

        return $this;
    }

    /**
     * Set an error.
     */
    public function setError(string $error): void
    {
        if (self::ERROR_FILE_EXISTS !== $error) {
            return;
        }

        $this->setMessages(
            [
                'meeting' => [
                    $this->translator->translate('There already are minutes for this meeting'),
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
            'upload' => [
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
