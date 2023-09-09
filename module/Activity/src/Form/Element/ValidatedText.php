<?php

declare(strict_types=1);

namespace Activity\Form\Element;

use Laminas\Filter\StringTrim;
use Laminas\Form\Element\Text;
use Laminas\InputFilter\InputProviderInterface;
use Laminas\Validator\NotEmpty;
use Laminas\Validator\ValidatorInterface;

class ValidatedText extends Text implements InputProviderInterface
{
    protected ?ValidatorInterface $validator = null;

    /**
     * Get primary validator.
     */
    protected function getValidator(): ValidatorInterface
    {
        if (null === $this->validator) {
            $this->validator = new NotEmpty(NotEmpty::STRING);
        }

        return $this->validator;
    }

    /**
     * Provide default input rules for this element
     *
     * Attaches a not empty validator and a string trim filter.
     *
     * @inheritDoc
     */
    public function getInputSpecification(): array
    {
        $spec = [
            'required'   => true,
            'filters'    => [
                ['name' => StringTrim::class],
            ],
            'validators' => [
                $this->getValidator(),
            ],
        ];

        $name = $this->getName();
        if (null !== $name) {
            $spec['name'] = $name;
        }

        return $spec;
    }
}
