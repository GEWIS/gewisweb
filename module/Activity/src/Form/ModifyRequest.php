<?php

namespace Activity\Form;

use Laminas\Form\Form;
use Laminas\InputFilter\InputFilterProviderInterface;

/**
 * Specifies a form that is used to let an user do a modification request that
 * does not require any other data, such as signing off for activities or
 * approving or disapproving them.
 */
class ModifyRequest extends Form implements InputFilterProviderInterface
{
    public function __construct($name = null, $buttonvalue = 'submit')
    {
        parent::__construct($name);
        $this->setAttribute('method', 'post');

        $this->add(
            [
                'name' => 'security',
                'type' => 'Laminas\Form\Element\Csrf',
            ]
        );

        $this->add(
            [
                'name' => 'submit',
                'attributes' => [
                    'type' => 'submit',
                    'value' => $buttonvalue,
                ],
            ]
        );
    }

    public function getInputFilterSpecification()
    {
        return [];
    }
}
