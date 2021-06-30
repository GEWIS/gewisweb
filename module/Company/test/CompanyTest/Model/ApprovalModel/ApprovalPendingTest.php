<?php

namespace Company\Model\ApprovalModel;


use Company\Mapper\BannerPackage;
use Company\Model\Company;
use Company\Model\CompanyBannerPackage;
use Company\Model\CompanyJobPackage;

class ApprovalPendingTest extends \PHPUnit_Framework_TestCase
{
    public function testPendingApprovalInitialState()
    {
        $pending = new ApprovalPending();

        $this->assertNull($pending->getId());
        $this->assertNull($pending->getVacancyApproval());
        $this->assertNull($pending->getBannerApproval());
        $this->assertNull($pending->getProfileApproval());
        $this->assertNull($pending->getCompany());
        $this->assertNull($pending->getStatus());
        $this->assertNull($pending->getType());
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

    public function testCompany() {
        $pending = new ApprovalPending();

        $this->assertNull($pending->getCompany());
        // Test Profile Company
        $profileCompany = new Company();
        $profileCompany->setId(1);
        $profile = new ApprovalProfile();
        $profile->setCompany($profileCompany);
        $pending->setProfileApproval($profile);
        $this->assertEquals($profileCompany, $pending->getCompany());
        $pending->setProfileApproval(null);
        // Test Banner Company
        $bannerCompany = new Company();
        $bannerCompany->setId(2);
        $banner = new CompanyBannerPackage();
        $banner->setCompany($bannerCompany);
        $pending->setBannerApproval($banner);
        $this->assertEquals($bannerCompany, $pending->getCompany());
        $pending->setBannerApproval(null);
        // Test Vacancy Company
        $vacancyCompany = new Company();
        $vacancyCompany->setId(3);
        $vacancy = new ApprovalVacancy();
        $package = new CompanyJobPackage();
        $package->setCompany($vacancyCompany);
        $vacancy->setPackage($package);
        $pending->setVacancyApproval($vacancy);
        $this->assertEquals($vacancyCompany, $pending->getCompany());
        $pending->setVacancyApproval(null);

        $this->assertNull($pending->getCompany());
    }

    public function testStatus() {
        $pending = new ApprovalPending();

        $this->assertNull($pending->getStatus());
        // Test Vacancy Status
        $vacancy = new ApprovalVacancy();
        $vacancy->setRejected(true);
        $pending->setVacancyApproval($vacancy);
        $this->assertTrue($pending->getStatus());
        $vacancy->setRejected(false);
        $this->assertFalse($pending->getStatus());
        $pending->setVacancyApproval(null);
        $this->assertNull($pending->getStatus());
        // Test Profile Status
        $profile = new ApprovalProfile();
        $profile->setRejected(true);
        $pending->setProfileApproval($profile);
        $this->assertTrue($pending->getStatus());
        $profile->setRejected(false);
        $this->assertFalse($pending->getStatus());
        $pending->setProfileApproval(null);
        $this->assertNull($pending->getStatus());
    }

    public function testType() {
        $pending = new ApprovalPending();

        $this->assertNull($pending->getType());
        $pending->setType("banner");
        $this->assertEquals("banner", $pending->getType());
        $pending->setType("vacancy");
        $this->assertEquals("vacancy", $pending->getType());
        $pending->setType("profile");
        $this->assertEquals("profile", $pending->getType());
        $pending->setType(null);
        $this->assertNull($pending->getType());
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

    public function testRejected() {
        $pending = new ApprovalPending();

        $this->assertFalse($pending->getRejected());
        $pending->setRejected(true);
        $this->assertTrue($pending->getRejected());
    }
}
