<?php

namespace DecisionTest\Service;

use PHPUnit_Framework_TestCase;
use Zend\ServiceManager\ServiceManager;

include_once 'PHPUnit/Extensions/Database/TestCase.php';

class DatabaseTest extends PHPUnit_Framework_TestCase
{
    function getConnection(){
        return new PHPunit_Extentsions_Database_DB_DefaultDatabaseConnection

    }

}
