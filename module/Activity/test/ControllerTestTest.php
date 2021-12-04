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

    public function testActivityArchiveActionCanBeAccessed()
    {
        $this->dispatch('/activity/archive');
        $this->assertResponseStatusCode(200);
    }

    public function testActivityCareerActionCanBeAccessed()
    {
        $this->dispatch('/activity/career');
        $this->assertResponseStatusCode(200);
    }
}
