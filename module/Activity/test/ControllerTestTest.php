<?php

namespace ActivityTest;

use ApplicationTest\BaseControllerTest;

class ControllerTestTest extends BaseControllerTest
{
    public function testActivityActionCanBeAccessed(): void
    {
        $this->dispatch('/activity');
        $this->assertResponseStatusCode(200);
    }

    public function testActivityArchiveActionCanBeAccessed(): void
    {
        $this->dispatch('/activity/archive');
        $this->assertResponseStatusCode(200);
    }

    public function testActivityCareerActionCanBeAccessed(): void
    {
        $this->dispatch('/activity/career');
        $this->assertResponseStatusCode(200);
    }
}
