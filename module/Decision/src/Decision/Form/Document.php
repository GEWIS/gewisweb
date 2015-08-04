<?php

namespace Decision\Form;

use Zend\Form\Form;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterProviderInterface;
use Zend\I18n\Translator\TranslatorInterface as Translator;

use Decision\Mapper\Meeting as MeetingMapper;

class Document extends Form implements InputFilterProviderInterface
{

    public function __construct(Translator $translator, MeetingMapper $mapper)
    {
        parent::__construct();
        $this->translator = $translator;

        $options = array();
        foreach ($mapper->findAll() as $meeting) {
            $meeting = $meeting[0];
            $name = $meeting->getType() . '/' . $meeting->getNumber();
            $options[$name] = $meeting->getType() . ' ' . $meeting->getNumber()
                            . ' (' . $meeting->getDate()->format('Y-m-d') . ')';
        }

        $this->add(array(
            'name' => 'meeting',
            'type' => 'select',
            'options' => array(
                'label' => $translator->translate('Meeting'),
                'empty_option' => $translator->translate('Choose a meeting'),
                'value_options' => $options
            )
        ));

        $this->add(array(
            'name' => 'name',
            'type' => 'text',
        ));
        $this->get('name')->setLabel($translator->translate('Document name'));

        $this->add(array(
            'name' => 'upload',
            'type' => 'file',
        ));
        $this->get('upload')->setLabel($translator->translate('Document to upload'));

        $this->add(array(
            'name' => 'submit',
            'type' => 'submit',
            'attributes' => array(
                'value' => $translator->translate('Upload document')
            )
        ));
    }

    /**
     * Input filter specification.
     */
    public function getInputFilterSpecification()
    {
        return array(
            'name' => array(
                'required' => true,
                'validators' => array(
                    array(
                        'name' => 'string_length',
                        'options' => array(
                            'min' => 2,
                            'max' => 128
                        )
                    )
                )
            ),
            'upload' => array(
                'required' => true,
                'validators' => array(
                    array(
                        'name' => 'File\Extension',
                        'options' => array(
                            'extension' => 'pdf'
                        )
                    ),
                    array(
                        'name' => 'File\MimeType',
                        'options' => array(
                            'mimeType' => 'application/pdf'
                        )
                    )
                )
            )
        );
    }

}
