<?php

namespace Activity\Form;

use Laminas\Form\Element\{
    Csrf,
    Submit,
};
use Laminas\Form\Form;
use Laminas\InputFilter\InputFilterProviderInterface;

/**
 * Specifies a form that is used to let an user do a modification request that
 * does not require any other data, such as signing off for activities or
 * approving or disapproving them.
 */
class ModifyRequest extends Form implements InputFilterProviderInterface
{
    /**
     * @param string|null $name
     * @param string $buttonvalue
     */
    public function __construct(
        ?string $name = null,
        string $buttonvalue = 'submit',
    ) {
        parent::__construct($name);
        $this->setAttribute('method', 'post');

        $this->add(
            [
                'name' => 'security',
                'type' => Csrf::class,
            ]
        );

        $this->add(
            [
                'name' => 'submit',
                'type' => Submit::class,
                'attributes' => [
                    'value' => $buttonvalue,
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
