<?php

namespace Education\Oase;

class Property {

    public function __construct($Naam = null, $Type = null, $Waarde = null) {
        $this->Waarde = $Waarde;
        $this->Type = $Type;
        $this->Naam = $Naam;
    }
    public $Waarde; // string
    public $Type; // string
    public $Naam; // string
}
