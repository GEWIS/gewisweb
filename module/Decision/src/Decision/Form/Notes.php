<?php

namespace Decision\Form;

use Zend\Form\Form;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterProviderInterface;
use Zend\I18n\Translator\TranslatorInterface as Translator;

use Doctrine\Common\Persistence\ObjectManager;

class Notes extends Form implements InputFilterProviderInterface
{

    public function __construct(Translator $translator, ObjectManager $om)
    {
        parent::__construct();

        $this->add(array(
            'name' => 'meeting',
            'type' => 'DoctrineModule\Form\Element\ObjectSelect',
            'options' => array(
                'object_manager' => $om,
                'target_class' => 'Decision\Model\Meeting',
                'label_generator' => function($meeting) {
                    return $meeting->getType() . ' ' . $meeting->getNumber();
                },
                'optgroup_identifier' => 'type',
                'find_method' => array(
                    'name' => 'findBy',
                    'params' => array(
                        'criteria' => array(),
                        'orderBy' => array('date' => 'DESC')
                    )
                ),
                'label' => $translator->translate('Meeting')
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
