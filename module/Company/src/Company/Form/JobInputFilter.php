<?php

namespace Company\Form;

use Zend\InputFilter\InputFilter;

class JobInputFilter extends InputFilter
{
    /**
     * Set data to use when validating and filtering
     *
     * @param  array|Traversable $data
     * @return InputFilterInterface
     */
    public function setData($data)
    {

        if($data['active'] == '0'){
            $this->setValidationGroup(['active']);
        }

        // Forward to default setData method
        return parent::setData($data);
    }
    public function setValidationGroup($arr)
    {
        return parent::setValidationGroup($arr);
    }
}
