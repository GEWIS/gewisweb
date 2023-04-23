<?php

declare(strict_types=1);

namespace Application\Form;

use Laminas\Form\Element\{
    Csrf,
    Submit,
};
use Laminas\Form\Form;
use Laminas\InputFilter\InputFilterProviderInterface;

/**
 * Specifies a form that is used to let a user do a modification request that does not require any other data, such as
 * signing off for activities, (dis)approving entities with an {@see \Application\Model\Traits\ApprovableTrait}.
 */
class ModifyRequest extends Form implements InputFilterProviderInterface
{
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
