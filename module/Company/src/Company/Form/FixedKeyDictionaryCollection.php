<?php
namespace Company\Form;

use Zend\Form\Element\Collection;
use Zend\Form\FieldsetInterface;

class FixedKeyDictionaryCollection extends Collection
{
    public function setOptions($options)
    {
        parent::setOptions($options);
        if (isset($options['items'])) {
            $items = $options['items'];
            foreach ($items as $x) {
                $this->addNewTargetElementInstance($x);
                $fs = $this->get($x);
                $fs->setLanguage($x);
            }
        }
        return $this;
    }

    // Return a dictionary instead of an array
    public function bindValues(array $values = array())
    {
        $collection = [];
        foreach ($values as $name => $value) {
            $element = $this->get($name);
            $collection[$name] = $element instanceof FieldsetInterface ? $element->bindValues($value) : $value;
        }
        return $collection;
    }
}
