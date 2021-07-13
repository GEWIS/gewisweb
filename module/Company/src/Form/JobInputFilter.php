<?php

namespace Company\Form;

use Laminas\InputFilter\InputFilter;

class JobInputFilter extends InputFilter
{
    protected function validateInputs(array $inputs, $data = [], $context = null)
    {
        if (!array_key_exists('active', $data) || '0' === $data['active']) {
            return true;
        }

        return parent::validateInputs($inputs, $data, $context);
    }
}
