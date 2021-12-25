<?php

namespace FrontpageTest;

use ApplicationTest\BaseControllerTest;

class ControllerTest extends BaseControllerTest
{
    public function testIndexActionCanBeAccessed(): void
    {
        $this->dispatch('/');
        $this->assertResponseStatusCode(200);
    }

    public function testLangEnDoesRedirect(): void
    {
        $this->dispatch('/lang/en/');
        $this->assertResponseStatusCode(302);
    }

    public function testLangNlDoesRedirect(): void
    {
        $this->dispatch('/lang/nl/');
        $this->assertResponseStatusCode(302);
    }

    public function testPollHistoryActionCanBeAccessed(): void
    {
        $this->dispatch('/poll/history');
        $this->assertResponseStatusCode(200);
    }

    public function testPollRequestActionIsForbidden(): void
    {
        $this->dispatch('/poll/request');
        $this->assertResponseStatusCode(403);
    }
}
