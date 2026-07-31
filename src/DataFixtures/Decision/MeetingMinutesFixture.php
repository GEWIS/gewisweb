<?php

declare(strict_types=1);

namespace App\DataFixtures\Decision;

use App\DataFixtures\User\UserFixture;
use App\Entity\Application\Enums\StorageNamespace;
use App\Entity\Decision\Meeting;
use App\Entity\Decision\MeetingMinutes;
use App\Entity\Decision\MeetingMinutesVersion;
use App\Entity\User\User;
use App\Service\Application\FileStorage;
use DateTime;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Override;
use RuntimeException;

use function file_put_contents;
use function sprintf;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

/**
 * Minutes on ALV-0 with a revision, so the oldest past ALV shows as complete while ALV-1 (no minutes, no decisions)
 * shows as still being processed.
 */
class MeetingMinutesFixture extends Fixture implements DependentFixtureInterface
{
    public function __construct(private readonly FileStorage $fileStorage)
    {
    }

    #[Override]
    public function load(ObjectManager $manager): void
    {
        $uploader = $this->getReference(
            'user-8000',
            User::class,
        );

        $meeting = $this->getReference(
            'meeting-ALV-0',
            Meeting::class,
        );

        $minutes = new MeetingMinutes();
        $minutes->setMeeting($meeting);

        $manager->persist($minutes);

        foreach (['v1.0', 'v1.1'] as $revision => $label) {
            $version = new MeetingMinutesVersion();
            $version->setMinutes($minutes);
            $version->setVersionLabel($label);
            $version->setPath($this->storePdf(
                'Minutes ALV-0 ' . $label,
                $meeting,
            ));
            $version->setUploadedBy($uploader);
            $version->setUploadedAt(new DateTime('-' . (2 - $revision) . ' days'));

            $manager->persist($version);
            $manager->flush();
        }
    }

    /**
     * @return class-string<Fixture>[]
     */
    #[Override]
    public function getDependencies(): array
    {
        return [
            MeetingFixture::class,
            UserFixture::class,
        ];
    }

    private function storePdf(
        string $marker,
        Meeting $meeting,
    ): string {
        $temporaryFile = tempnam(
            sys_get_temp_dir(),
            'fixture-pdf-',
        );

        if (false === $temporaryFile) {
            throw new RuntimeException('Could not create a temporary file for a fixture PDF.');
        }

        file_put_contents(
            $temporaryFile,
            sprintf(
                "%%PDF-1.4\n%% %s\n%%%%EOF\n",
                $marker,
            ),
        );

        try {
            return $this->fileStorage->store(
                StorageNamespace::MeetingMinutes,
                $temporaryFile,
                $meeting->getStorageScope(),
            )->path;
        } finally {
            unlink($temporaryFile);
        }
    }
}
