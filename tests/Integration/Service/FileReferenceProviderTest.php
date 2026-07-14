<?php

declare(strict_types=1);

namespace App\Tests\Integration\Service;

use App\Entity\Career\CompanyBannerPackage;
use App\Entity\Career\CompanyRevision;
use App\Entity\Decision\Organ;
use App\Entity\Decision\OrganInformation;
use App\Service\Application\FileStorage;
use App\Service\Career\CompanyBannerReferenceProvider;
use App\Service\Career\CompanyLogoReferenceProvider;
use App\Service\Decision\OrganImageReferenceProvider;
use App\Tests\Integration\DatabaseTestCase;

/**
 * The reference providers back {@see FileStorage::remove()}'s GH-583 guarantee: a content-addressed file is only
 * unlinked once no domain still points at it. Each provider reports on its own table, and all of them are auto-tagged
 * so the storage service consults them, which the final test proves end to end against the seeded MariaDB.
 */
final class FileReferenceProviderTest extends DatabaseTestCase
{
    public function testCompanyLogoIsReferencedWhileARevisionPointsAtIt(): void
    {
        $revision = $this->entityManager->getRepository(CompanyRevision::class)->findOneBy([]);
        self::assertInstanceOf(
            CompanyRevision::class,
            $revision,
            'The seed is expected to contain a company revision.',
        );
        $revision->setLogo('career/1/images/reference-test.png');
        $this->entityManager->flush();

        $provider = new CompanyLogoReferenceProvider($this->entityManager);

        self::assertTrue($provider->references('career/1/images/reference-test.png'));
        self::assertFalse($provider->references('career/1/images/not-referenced.png'));
    }

    public function testCompanyBannerIsReferencedWhileAPackagePointsAtIt(): void
    {
        $package = $this->entityManager->getRepository(CompanyBannerPackage::class)->findOneBy([]);
        self::assertInstanceOf(
            CompanyBannerPackage::class,
            $package,
            'The seed is expected to contain a company banner package.',
        );
        $package->setImage('career/2/images/banner-reference-test.png');
        $this->entityManager->flush();

        $provider = new CompanyBannerReferenceProvider($this->entityManager);

        self::assertTrue($provider->references('career/2/images/banner-reference-test.png'));
        self::assertFalse($provider->references('career/2/images/not-referenced.png'));
    }

    public function testOrganCoverAndThumbnailAreBothReferenced(): void
    {
        // The seed has organs but no organ information yet, so attach a fresh one carrying the two image paths.
        $organ = $this->entityManager->getRepository(Organ::class)->findOneBy([]);
        self::assertInstanceOf(
            Organ::class,
            $organ,
            'The seed is expected to contain an organ.',
        );
        $information = new OrganInformation();
        $information->setOrgan($organ);
        $information->setCoverPath('organs/images/cover-reference-test.png');
        $information->setThumbnailPath('organs/images/thumbnail-reference-test.png');
        $this->entityManager->persist($information);
        $this->entityManager->flush();

        $provider = new OrganImageReferenceProvider($this->entityManager);

        self::assertTrue($provider->references('organs/images/cover-reference-test.png'));
        self::assertTrue($provider->references('organs/images/thumbnail-reference-test.png'));
        self::assertFalse($provider->references('organs/images/not-referenced.png'));
    }

    public function testStorageRemoveDeclinesWhileAReferenceExists(): void
    {
        $revision = $this->entityManager->getRepository(CompanyRevision::class)->findOneBy([]);
        self::assertInstanceOf(
            CompanyRevision::class,
            $revision,
        );
        $revision->setLogo('career/1/images/still-referenced.png');
        $this->entityManager->flush();

        // The provider is auto-tagged, so FileStorage must refuse to unlink a file a revision still references.
        $fileStorage = self::getContainer()->get(FileStorage::class);
        self::assertFalse($fileStorage->remove('career/1/images/still-referenced.png'));
    }
}
