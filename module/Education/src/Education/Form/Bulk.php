<?php

namespace Education\Form;

use Zend\Form\Form;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterProviderInterface;
use Zend\I18n\Translator\TranslatorInterface as Translator;

class Bulk extends Form implements InputFilterProviderInterface
{

    public function __construct(Translator $translator, Fieldset\Exam $exam)
    {
        parent::__construct();

        $this->add(array(
            'name' => 'exams',
            'type' => 'Collection',
            'options' => array(
                'count' => 2,
                'allow_add' => true,
                'allow_remove' => true,
                'target_element' => clone $exam
            )
        ));
    }

    public function getInputFilterSpecification()
    {
        return array();
    }

}
