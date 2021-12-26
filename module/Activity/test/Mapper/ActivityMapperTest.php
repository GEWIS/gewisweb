<?php

namespace ActivityTest\Mapper;

use Activity\Mapper\Activity as ActivityMapper;
use Activity\Model\Activity;
use Application\Mapper\BaseMapper;
use ApplicationTest\Mapper\BaseMapperTest;
use Decision\Model\Member;
use User\Model\User;

class ActivityMapperTest extends BaseMapperTest
{
    /**
     * @var ActivityMapper
     */
    protected BaseMapper $mapper;
    protected User $user;

    public function setUp(): void
    {
        parent::setUp();
        $this->mapper = $this->serviceManager->get('activity_mapper_activity');
        $this->object = new Activity();

        $this->setUpUser();
    }

    protected function setUpUser(): void
    {
        $this->user = new User();
        $this->user->setLidnr(8000);

        $this->member = new Member();
        $this->member->setLidnr(8000);

        $this->user->setMember($this->member);
    }

    public function testGetUpcomingActivitiesForMember(): void
    {
        $this->mapper->getUpcomingActivitiesForMember($this->user);
        $this->expectNotToPerformAssertions();
    }
}
