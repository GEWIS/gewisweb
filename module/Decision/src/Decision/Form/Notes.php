<?php

namespace Decision\Form;

use Zend\Form\Form;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterProviderInterface;
use Zend\I18n\Translator\TranslatorInterface as Translator;

use Doctrine\Common\Persistence\ObjectManager;

use Decision\Mapper\Meeting as MeetingMapper;

class Notes extends Form implements InputFilterProviderInterface
{

    public function __construct(Translator $translator, MeetingMapper $mapper)
    {
        parent::__construct();

        $options = array();
        foreach ($mapper->findAll() as $meeting) {
            $meeting = $meeting[0];
            $name = $meeting->getType() . ' ' . $meeting->getNumber();
            $options[$name] = $name;
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
            'name' => 'upload',
            'type' => 'file',
            'option' => array(
                'label' => $translator->translate('Notes to upload')
            )
        ));
        $this->get('upload')->setLabel($translator->translate('Notes to upload'));

        $this->add(array(
            'name' => 'submit',
            'type' => 'submit',
            'attributes' => array(
                'value' => $translator->translate('Submit')
            )
        ));
    }

    /**
     * Input filter specification.
     */
    public function getInputFilterSpecification()
    {
        return array(
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
