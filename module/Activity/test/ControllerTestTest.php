<?php

namespace ActivityTest;

use ApplicationTest\BaseControllerTest;

class ControllerTestTest extends BaseControllerTest
{
    public function testActivityActionCanBeAccessed()
    {
        $this->dispatch('/activity');
        $this->assertResponseStatusCode(200);
    }
}
