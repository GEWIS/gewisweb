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

        var_dump($data);
        if($data['active'] == '0'){
            echo "Set Validation Group";
            $this->setValidationGroup(['active']);
        }

        // Forward to default setData method
        return parent::setData($data);
    }
    public function setValidationGroup($arr)
    {
        var_dump($arr);
        var_dump(debug_backtrace());
        return parent::setValidationGroup($arr);
    }
    /**
     * Validate a set of inputs against the current data
     *
     * @param  string[] $inputs Array of input names.
     * @param  array|ArrayAccess $data
     * @param  mixed|null $context
     * @return bool
     */
    protected function validateInputs(array $inputs, $data = [], $context = null)
    {
        $inputContext = $context ?: (array_merge($this->getRawValues(), (array) $data));
        $this->validInputs   = [];
        $this->invalidInputs = [];
        $valid               = true;
        echo "NONO";
        var_dump($this->validationGroup);
        var_dump($inputs);
        foreach ($inputs as $name) {
            $input       = $this->inputs[$name];
            // Validate an input filter
            if ($input instanceof InputFilterInterface) {
                if (! $input->isValid($context)) {
                    $this->invalidInputs[$name] = $input;
                    $valid = false;
                    continue;
                }
                $this->validInputs[$name] = $input;
                continue;
            }
            // If input is not InputInterface then silently continue (BC safe)
            if (! $input instanceof InputInterface) {
                continue;
            }
            // If input is optional (not required), and value is not set, then ignore.
            if (! array_key_exists($name, $data)
                && ! $input->isRequired()
            ) {
                continue;
            }
            // Validate an input
            if (! $input->isValid($inputContext)) {
                // Validation failure
                $this->invalidInputs[$name] = $input;
                $valid = false;
                if ($input->breakOnFailure()) {
                    return false;
                }
                continue;
            }
            $this->validInputs[$name] = $input;
        }
        return $valid;
    }
}
