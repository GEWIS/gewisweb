<?php

namespace Company\Model\ApprovalModel;


use Company\Mapper\BannerPackage;
use Company\Model\CompanyBannerPackage;

class ApprovalPendingTest extends \PHPUnit_Framework_TestCase
{
    public function testPendingApprovalInitialState()
    {
        $pending = new ApprovalPending();

        $this->assertNull($pending->getId());
        $this->assertNull($pending->getVacancyApproval());
        $this->assertNull($pending->getBannerApproval());
        $this->assertNull($pending->getProfileApproval());
//        $this->assertNull($pending->getCompany());
//        $this->assertNull($pending->getStatus());
//        $this->assertNull($pending->getType());
        $this->assertFalse($pending->getRejected());
    }

    public function testId() {
        $pending = new ApprovalPending();

        $this->assertNull($pending->getId());
        $pending->setId(1);
        $this->assertEquals(1, $pending->getId());
        $pending->setId(99999);
        $this->assertEquals(99999, $pending->getId());
        $pending->setId(null);
        $this->assertNull($pending->getId());
    }

    public function testVacancyApproval() {
        $pending = new ApprovalPending();
        $vacancy = new ApprovalVacancy();

        $this->assertNull($pending->getVacancyApproval());
        $pending->setVacancyApproval($vacancy);
        $this->assertEquals($vacancy, $pending->getVacancyApproval());
        $pending->setVacancyApproval(null);
        $this->assertNull($pending->getVacancyApproval());
    }

    public function testProfileApproval() {
        $pending = new ApprovalPending();
        $profile = new ApprovalProfile();

        $this->assertNull($pending->getProfileApproval());
        $pending->setProfileApproval($profile);
        $this->assertEquals($profile, $pending->getProfileApproval());
        $pending->setProfileApproval(null);
        $this->assertNull($pending->getProfileApproval());
    }

    public function testBannerApproval() {
        $pending = new ApprovalPending();
        $banner = new CompanyBannerPackage();

        $this->assertNull($pending->getBannerApproval());
        $pending->setBannerApproval($banner);
        $this->assertEquals($banner, $pending->getBannerApproval());
        $pending->setBannerApproval(null);
        $this->assertNull($pending->getBannerApproval());
    }
}
