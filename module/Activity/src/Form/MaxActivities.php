<?php

namespace Activity\Form;

use Laminas\Form\Element\{
    Hidden,
    Number,
};
use Laminas\Form\Fieldset;

class MaxActivities extends Fieldset
{
    public function __construct()
    {
        parent::__construct();

        $this->add(
            [
                'name' => 'id',
                'type' => Hidden::class,
            ]
        );

        $this->add(
            [
                'name' => 'name',
                'type' => Hidden::class,
            ]
        );

        $this->add(
            [
                'name' => 'value',
                'type' => Number::class,
                'options' => [
                    'min' => 0,
                ],
            ]
        );
    }
}
