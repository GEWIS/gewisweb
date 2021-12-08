<?php

namespace ApplicationTest;

use Laminas\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;

abstract class BaseControllerTest extends AbstractHttpControllerTestCase
{
    public function setUp(): void
    {
        $this->setApplicationConfig(
            include './config/application.config.php'
        );
        parent::setUp();
    }
}
