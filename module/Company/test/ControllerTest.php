<?php

namespace CompanyTest;

use ApplicationTest\BaseControllerTest;

class ControllerTest extends BaseControllerTest
{
    public function testCareerActionCanBeAccessed()
    {
        $this->dispatch('/career');
        $this->assertResponseStatusCode(200);
    }
}
