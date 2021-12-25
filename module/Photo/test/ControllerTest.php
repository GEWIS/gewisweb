<?php

namespace PhotoTest;

use ApplicationTest\BaseControllerTest;

class ControllerTest extends BaseControllerTest
{
    public function testIndexActionIsForbidden(): void
    {
        $this->dispatch('/photo');
        $this->assertResponseStatusCode(403);
    }
}
