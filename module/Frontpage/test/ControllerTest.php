<?php

namespace FrontpageTest;

use ApplicationTest\BaseControllerTest;

class ControllerTest extends BaseControllerTest
{
    public function testIndexActionCanBeAccessed()
    {
        $this->dispatch('/');
        $this->assertResponseStatusCode(200);
    }

    public function testLangEnDoesRedirect()
    {
        $this->dispatch('/lang/en/');
        $this->assertResponseStatusCode(302);
    }

    public function testLangNlDoesRedirect()
    {
        $this->dispatch('/lang/nl/');
        $this->assertResponseStatusCode(302);
    }

    public function testPollHistoryActionCanBeAccessed()
    {
        $this->dispatch('/poll/history');
        $this->assertResponseStatusCode(200);
    }

    public function testPollRequestActionIsForbidden()
    {
        $this->dispatch('/poll/request');
        $this->assertResponseStatusCode(403);
    }
}
