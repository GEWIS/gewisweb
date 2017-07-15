<?php

namespace Company\Form;

use Zend\InputFilter\InputFilter;

class JobInputFilter extends InputFilter
{
    protected function validateInputs(array $inputs, $data = array(), $context = null)
    {
        if ($data['active'] === '0') {
            return true;
        }
        return parent::validateInputs($inputs, $data, $context);
    }
}
