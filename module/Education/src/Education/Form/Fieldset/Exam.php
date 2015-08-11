<?php

namespace Education\Form\Fieldset;

use Zend\Form\Fieldset;
use Zend\InputFitler\InputFilterProviderInterface;
use zend\I18n\Translator\TranslatorInterface as Translator;

class Exam extends Fieldset
    implements InputFilterProviderInterface
{

    public function __construct(Translator $translator)
    {
        parent::__construct('exam');

        $this->add(array(
            'name' => 'file',
            'type' => 'hidden'
        ));

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
    }

    public function getInputFilterSpecification()
    {
        return array(
            'file' => array(
                'required' => true,
                'validators' => array(
                    array(
                        'name' => 'regex',
                        'options' => array(
                            'pattern' => '/^[a-fA-F0-9]+\.pdf$/'
                        )
                    ),
                    array(
                        'name' => 'File\Exists',
                        'options' => array(
                            'directory' => $this->config['upload_dir']
                        )
                    )
                )
            ),

            'course' => array(
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
            ),

            'date' => array(
                'required' => true,
                'validators' => array('name' => 'date')
            )
        )
    }

}
