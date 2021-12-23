<?php

namespace CompanyTest;

use ApplicationTest\BaseControllerTest;

class ControllerTest extends BaseControllerTest
{
    public function testCareerActionCanBeAccessed(): void
    {
        $this->dispatch('/career');
        $this->assertResponseStatusCode(200);
    }
}
