<?php

namespace EducationTest;

use ApplicationTest\BaseControllerTest;

class ControllerTest extends BaseControllerTest
{
    public function testEducationActionCanBeAccessed()
    {
        $this->dispatch('/education');
        $this->assertResponseStatusCode(200);
    }
}
