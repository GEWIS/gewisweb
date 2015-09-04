<?php

/**
 * @author   Jeremy Kendall 
 * @see Zend_Validate_Abstract
 */
require_once 'Zend/Validate/Abstract.php';

/**
 * Requires field presence based on provided value of radio element.  
 * 
 * Example would be radio element with Yes, No, Other option, followed by an "If 
 * other, please explain" text area.
 * 
 * IMPORTANT: For this validator to work, allowEmpty must be set to false on 
 * the child element being validated.
 * 
 * From Zend Framework Documentation 15.3: "By default, when an 
 * element is required, a flag, 'allowEmpty', is also true. This means that if 
 * a value evaluating to empty is passed to isValid(), the validators will be 
 * skipped. You can toggle this flag using the accessor setAllowEmpty($flag); 
 * when the flag is false, then if a value is passed, the validators will still 
 * run."
 * 
 * @uses     Zend_Validate_Abstract
 * @author   Jeremy Kendall 
 */

class FieldDependantValidator extends Zend_Validate_Abstract {

    /**
     * Validation failure message key for when the value of the parent field is an empty string
     */
    const KEY_NOT_FOUND  = 'keyNotFound';

    /**
     * Validation failure message key for when the value is an empty string
     */
    const KEY_IS_EMPTY   = 'keyIsEmpty';

    /**
     * Validation failure message template definitions
     *
     * @var array
     */
    protected $_messageTemplates = array(
        self::KEY_NOT_FOUND  => 'Parent field does not exist in form input',
        self::KEY_IS_EMPTY   => 'Based on your answer above, this field is required',
    );

    /**
     * Key to test against
     *
     * @var string|array
     */
    protected $_contextKey;

    /**
     * String to test for
     *
     * @var string
     */
    protected $_testValue;

    /**
     * FieldDependant constructor
     *
     * @param string $contextKey Name of parent field to test against
     * @param string $testValue Value of multi option that, if selected, child field required
     */
    public function __construct($contextKey, $testValue = null) {
        $this->setTestValue($testValue);
        $this->setContextKey($contextKey);
    }

    /**
     * Defined by Zend_Validate_Interface
     *
     * Wrapper around doValid()
     *
     * @param  string $value
     * @param  array  $context
     * @return boolean
     */
    public function isValid($value, $context = null) {

        echo 'Validator is used';
        $contextKey = $this->getContextKey();

        // If context key is an array, doValid for each context key
        if (is_array($contextKey)) {
            foreach ($contextKey as $ck) {
                $this->setContextKey($ck);
                if(!$this->doValid($value, $context)) {
                    return false;
                }
            }
        } else {
            if(!$this->doValid($value, $context)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Returns true if dependant field value is not empty when parent field value
     * indicates that the dependant field is required
     *
     * @param  string $value
     * @param  array  $context
     * @return boolean
     */
    public function doValid($value, $context = null) {
        $testValue  = $this->getTestValue();
        $contextKey = $this->getContextKey();
        $value      = (string) $value;
        $this->_setValue($value);

        if ((null === $context) || !is_array($context) || !array_key_exists($contextKey, $context)) {
            $this->_error(self::KEY_NOT_FOUND);
            return false;
        }

        if (is_array($context[$contextKey])) {
            $parentField = $context[$contextKey][0];
        } else {
            $parentField = $context[$contextKey];
        }

        if ($testValue) {
            if ($testValue == ($parentField) && empty($value)) {
                $this->_error(self::KEY_IS_EMPTY);
                return false;
            }
        } else {
            if (!empty($parentField) && empty($value)) {
                $this->_error(self::KEY_IS_EMPTY);
                return false;
            }
        }

        return true;
    }

    /**
     * @return string
     */
    protected function getContextKey() {
        return $this->_contextKey;
    }

    /**
     * @param string $contextKey
     */
    protected function setContextKey($contextKey) {
        $this->_contextKey = $contextKey;
    }

    /**
     * @return string
     */
    protected function getTestValue () {
        return $this->_testValue;
    }

    /**
     * @param string $testValue
     */
    protected function setTestValue ($testValue) {
        $this->_testValue = $testValue;
    }
}

