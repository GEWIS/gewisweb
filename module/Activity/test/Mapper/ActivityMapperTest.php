<?php

namespace ActivityTest\Mapper;

use Activity\Mapper\Activity as ActivityMapper;
use Activity\Model\{
    Activity,
    ActivityLocalisedText,
};
use Application\Mapper\BaseMapper;
use ApplicationTest\Mapper\BaseMapperTest;
use DateTime;
use Decision\Model\Member;
use User\Model\User;

class ActivityMapperTest extends BaseMapperTest
{
    /**
     * @var ActivityMapper
     */
    protected BaseMapper $mapper;
    protected User $user;
    protected Member $member;

    public function setUp(): void
    {
        parent::setUp();
        $this->mapper = $this->serviceManager->get('activity_mapper_activity');

        $this->setUpUser();

        $this->localisedText = new ActivityLocalisedText('activity', 'activiteit');

        $this->object = new Activity();
        $this->object->setName($this->localisedText);
        $this->object->setDescription($this->localisedText);
        $this->object->setLocation($this->localisedText);
        $this->object->setCosts($this->localisedText);
        $this->object->setCreator($this->user);
        $this->object->setBeginTime(new DateTime());
        $this->object->setEndTime(new DateTime());
        $this->object->setStatus(Activity::STATUS_APPROVED);
        $this->object->setIsMyFuture(false);
        $this->object->setRequireGEFLITST(false);
    }

    protected function setUpUser(): void
    {
        $this->user = new User();
        $this->user->setLidnr(8000);
        $this->user->setEmail('web@gewis.nl');
        $this->user->setPassword('');

        $this->member = new Member();
        $this->member->setLidnr(8000);
        $this->member->setEmail('web@gewis.nl');
        $this->member->setInitials('W.C.');
        $this->member->setFirstName('Web');
        $this->member->setMiddleName('');
        $this->member->setLastName('Committee');
        $this->member->setGender(Member::GENDER_OTHER);
        $this->member->setGeneration(2020);
        $this->member->setType(Member::TYPE_ORDINARY);
        $this->member->setChangedOn(new DateTime());
        $this->member->setBirth(new DateTime());
        $this->member->setExpiration(new DateTime());

        $this->user->setMember($this->member);

        $this->entityManager->persist($this->member);
        $this->entityManager->persist($this->user);
    }

    public function testGetUpcomingActivitiesForMember(): void
    {
        $this->mapper->getUpcomingActivitiesForMember($this->user);
        $this->expectNotToPerformAssertions();
    }
}
