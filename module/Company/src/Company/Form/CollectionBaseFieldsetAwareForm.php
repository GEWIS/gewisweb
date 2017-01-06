<?php

namespace Company\Form;

use Zend\Form\Form;
use Zend\Form\Element\Collection;
use Zend\InputFilter\InputFilter;
use Zend\Mvc\I18n\Translator;
use Zend\Form\FormInterface;

class CollectionBaseFieldsetAwareForm extends Form
{
    // Zf2 has a bug: it is not possible to bind an array to a form. However, this is needed
    // if the base fieldset is a Collection (or subclass)
    // This implementation fixes it
    public function bind($object, $flags = FormInterface::VALUES_NORMALIZED)
    {
        if (!in_array($flags, array(FormInterface::VALUES_NORMALIZED, FormInterface::VALUES_RAW))) {
            throw new Exception\InvalidArgumentException(sprintf(
                '%s expects the $flags argument to be one of "%s" or "%s"; received "%s"',
                __METHOD__,
                'Zend\Form\FormInterface::VALUES_NORMALIZED',
                'Zend\Form\FormInterface::VALUES_RAW',
                $flags
            ));
        }
        if ($this->baseFieldset !== null) {
            $this->baseFieldset->setObject($object);
        }
        $this->bindAs = $flags;
        // If the fieldset is an collection, setting the object of self raises an exception.
        // It is also not needed in this situation, so it is better not to do it.
        if (!is_array($object) || !($this->baseFieldset instanceof Collection)) {
            $this->setObject($object);
        }
        $data = $this->extract();
        $this->populateValues($data, true);
        return $this;
    }
}
