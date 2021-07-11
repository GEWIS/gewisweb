<?php

namespace Decision\Form;

use Zend\Form\Form;
use Zend\InputFilter\InputFilterProviderInterface;
use Zend\I18n\Translator\TranslatorInterface as Translator;

use Decision\Mapper\Meeting as MeetingMapper;
use Zend\Validator\File\Extension;
use Zend\Validator\File\MimeType;

class Notes extends Form implements InputFilterProviderInterface
{
    const ERROR_FILE_EXISTS = 'file_exists';

    protected $translator;

    public function __construct(Translator $translator, MeetingMapper $mapper)
    {
        parent::__construct();
        $this->translator = $translator;

        $options = [];
        foreach ($mapper->findAll() as $meeting) {
            $meeting = $meeting[0];
            $name = $meeting->getType() . '/' . $meeting->getNumber();
            $options[$name] = $meeting->getType() . ' ' . $meeting->getNumber()
                . ' (' . $meeting->getDate()->format('Y-m-d') . ')';
        }

        $this->add([
            'name' => 'meeting',
            'type' => 'select',
            'options' => [
                'label' => $translator->translate('Meeting'),
                'empty_option' => $translator->translate('Choose a meeting'),
                'value_options' => $options
            ]
        ]);

        $this->add([
            'name' => 'upload',
            'type' => 'file',
            'option' => [
                'label' => $translator->translate('Notes to upload')
            ]
        ]);
        $this->get('upload')->setLabel($translator->translate('Notes to upload'));

        $this->add([
            'name' => 'submit',
            'type' => 'submit',
            'attributes' => [
                'value' => $translator->translate('Submit')
            ]
        ]);
    }

    /**
     * Set an error.
     *
     * @param string $error
     */
    public function setError($error)
    {
        if ($error == self::ERROR_FILE_EXISTS) {
            $this->setMessages([
                'meeting' => [
                    $this->translator->translate('There already are notes for this meeting')
                ]
            ]);
        }
    }

    /**
     * Input filter specification.
     */
    public function getInputFilterSpecification()
    {
        return [
            'upload' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => Extension::class,
                        'options' => [
                            'extension' => 'pdf'
                        ]
                    ],
                    [
                        'name' => MimeType::class,
                        'options' => [
                            'mimeType' => 'application/pdf'
                        ]
                    ]
                ]
            ]
        ];
    }
}
