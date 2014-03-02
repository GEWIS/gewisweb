<?php

namespace Education\Form;

use Zend\Form\Form;
use Zend\InputFilter\InputFilter;
use Zend\I18n\Translator\Translator;

class Upload extends Form
{

    public function __construct(Translator $translator)
    {
        parent::__construct();

        $this->add(array(
            'name' => 'course',
            'type' => 'text',
            'options' => array(
                'label' => $translator->translate('Course code')
            )
        ));

        $this->add(array(
            'name' => 'date',
            'type' => 'date',
            'options' => array(
                'label' => $translator->translate('Exam date')
            )
        ));

        $this->add(array(
            'name' => 'upload',
            'type' => 'file',
            'option' => array(
                'label' => $translator->translate('Exam to upload')
            )
        ));
        $this->get('upload')->setLabel($translator->translate('Exam to upload'));

        $this->add(array(
            'name' => 'submit',
            'type' => 'submit',
            'attributes' => array(
                'value' => $translator->translate('Submit')
            )
        ));

        $this->initFilters();
    }

    protected function initFilters()
    {
        $filter = new InputFilter();


        $filter->add(array(
            'name' => 'course',
            'required' => true,
            'validators' => array(
                array(
                    'name' => 'string_length',
                    'options' => array(
                        'min' => 5,
                        'max' => 6
                    )
                ),
                array('name' => 'alnum')
            ),
            'filters' => array(
                array('name' => 'string_to_upper')
            )
        ));

        $filter->add(array(
            'name' => 'date',
            'required' => true,
            'validators' => array(
                array('name' => 'date')
            )
        ));

        $filter->add(array(
            'name' => 'upload',
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
        ));

        $this->setInputFilter($filter);
    }
}
