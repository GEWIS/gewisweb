<?php

namespace Company\Form;

use Zend\Form\Fieldset;

/**
 *
 */
class JobLabelFieldset extends Fieldset
{
    public function __construct($translator, $labels)
    {
        parent::__construct();

        $labelOptions = [];
        foreach ($labels as $label) {
            $labelOptions[$label->getId()] = $label->getName();
        }

        $this->add([
            'name' => 'label',
            'type' => 'select',
            'labels' => [
                'empty_option' => [
                    'label' => $translator->translate('Select a label'),
                    'selected' => 'selected',
                    'disabled' => 'disabled',
                ],
                'value_options' => $labelOptions
            ]
        ]);
    }
}
