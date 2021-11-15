<?php

namespace Education\Form;

use Laminas\Form\Element\{
    Collection,
    Submit,
};
use Laminas\Form\Fieldset;
use Laminas\Form\Form;
use Laminas\Mvc\I18n\Translator;
use Laminas\InputFilter\InputFilterProviderInterface;

class Bulk extends Form implements InputFilterProviderInterface
{
    public function __construct(Translator $translator, Fieldset $exam)
    {
        parent::__construct();

        $this->add(
            [
                'name' => 'exams',
                'type' => Collection::class,
                'options' => [
                    'count' => 0,
                    'allow_add' => true,
                    'allow_remove' => true,
                    'target_element' => $exam,
                ],
            ]
        );

        $this->add(
            [
                'name' => 'submit',
                'type' => Submit::class,
                'attributes' => [
                    'value' => $translator->translate('Finalize uploads'),
                ],
            ]
        );
    }

    /**
     * @return array
     */
    public function getInputFilterSpecification(): array
    {
        return [];
    }
}
