<?php

namespace Education\Oase;

class Vraag {

    public function __construct($ServiceNaam)
    {
        $this->ServiceNaam = $ServiceNaam;
        $this->Property = array();
    }

    public function addProperty(Property $property)
    {
        $this->Property[] = $property;
    }

    public $ServiceNaam; // string
    public $Property; // Property
}
