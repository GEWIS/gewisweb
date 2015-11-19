<?php

namespace Education\Form;

use Zend\Form\Form;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterProviderInterface;
use Zend\I18n\Translator\TranslatorInterface as Translator;

class TempUpload extends Form implements InputFilterProviderInterface
{

    public function __construct(Translator $translator)
    {
        parent::__construct();

        $this->add(array(
            'name' => 'file',
            'type' => 'file',
            'option' => array(
                'label' => $translator->translate('Exam to upload')
            )
        ));
        $this->get('file')->setLabel($translator->translate('Exam to upload'));
    }

    public function getInputFilterSpecification()
    {
        return array(
            'file' => array(
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
