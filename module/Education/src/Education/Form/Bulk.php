<?php

namespace Education\Form;

use Laminas\Form\Form;
use Laminas\Form\Fieldset;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\I18n\Translator\TranslatorInterface as Translator;

class Bulk extends Form implements InputFilterProviderInterface
{
    public function __construct(Translator $translator, Fieldset $exam)
    {
        parent::__construct();

        $this->add(
            [
            'name' => 'exams',
            'type' => 'Collection',
            'options' => [
                'count' => 0,
                'allow_add' => true,
                'allow_remove' => true,
                'target_element' => $exam
            ]
            ]
        );

        $this->add(
            [
            'name' => 'submit',
            'type' => 'submit'
            ]
        );

        $this->get('submit')->setLabel($translator->translate('Finalize uploads'));
    }

    public function getInputFilterSpecification()
    {
        return [];
    }
}
