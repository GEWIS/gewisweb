<?php

namespace ActivityTest\Mapper;

use ApplicationTest\Mapper\BaseMapperTest;

class ActivityMapperTest extends BaseMapperTest
{
    public function setUp(): void
    {
        parent::setUp();
        $this->mapper = $this->serviceManager->get('activity_mapper_activity');
    }
}
