<?php

namespace PhotoTest;

use ApplicationTest\BaseControllerTest;

class ControllerTest extends BaseControllerTest
{
    public function testIndexActionCanNotBeAccessed()
    {
        $this->dispatch('/photo');
        $this->assertResponseStatusCode(403);
    }
}
