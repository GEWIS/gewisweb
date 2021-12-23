<?php

namespace Decision\Form;

use Laminas\Form\Element\{
    File,
    Select,
    Submit,
};
use Laminas\Form\Form;
use Laminas\Mvc\I18n\Translator;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Validator\File\{
    Extension,
    MimeType,
};

class Notes extends Form implements InputFilterProviderInterface
{
    public const ERROR_FILE_EXISTS = 'file_exists';

    /**
     * @var Translator
     */
    protected Translator $translator;

    public function __construct(Translator $translator)
    {
        parent::__construct();
        $this->translator = $translator;

        $this->add(
            [
                'name' => 'meeting',
                'type' => Select::class,
                'options' => [
                    'label' => $this->translator->translate('Meeting'),
                    'empty_option' => $this->translator->translate('Choose a meeting'),
                    'value_options' => [],
                ],
            ]
        );

        $this->add(
            [
                'name' => 'upload',
                'type' => File::class,
                'option' => [
                    'label' => $translator->translate('Notes to upload'),
                ],
            ]
        );

        $this->add(
            [
                'name' => 'submit',
                'type' => Submit::class,
                'attributes' => [
                    'value' => $translator->translate('Submit'),
                ],
            ]
        );
    }

    /**
     * @param array $meetings
     *
     * @return Notes
     */
    public function setMeetings(array $meetings): self
    {
        $options = [];
        foreach ($meetings as $meeting) {
            $meeting = $meeting[0];
            $name = $meeting->getType() . '/' . $meeting->getNumber();
            $options[$name] = $meeting->getType() . ' ' . $meeting->getNumber()
                . ' (' . $meeting->getDate()->format('Y-m-d') . ')';
        }

        $this->get('meeting')->setValueOptions($options);

        return $this;
    }

    /**
     * Set an error.
     *
     * @param string $error
     */
    public function setError(string $error): void
    {
        if (self::ERROR_FILE_EXISTS == $error) {
            $this->setMessages(
                [
                    'meeting' => [
                        $this->translator->translate('There already are notes for this meeting'),
                    ],
                ]
            );
        }
    }

    /**
     * Input filter specification.
     *
     * @return array
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
