<?php

declare(strict_types=1);

namespace App\DataFixtures\Decision;

use App\DataFixtures\User\UserFixture;
use App\Entity\Application\Enums\StorageNamespace;
use App\Entity\Decision\Meeting;
use App\Entity\Decision\MeetingReferenceSelection;
use App\Entity\Decision\ReferenceDocument;
use App\Entity\Decision\ReferenceDocumentVersion;
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
 * A small reference library: a twice-revised document that ALV-0 pins to its original version while ALV-1 and the
 * upcoming ALV-3 follow the latest, and a single-version document selected for the upcoming ALV only.
 */
class ReferenceDocumentFixture extends Fixture implements DependentFixtureInterface
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

        $scenarios = new ReferenceDocument();
        $scenarios->setName('Scenarios and Procedures');
        $manager->persist($scenarios);

        $scenariosVersions = [];
        foreach (['v3.0', 'v3.1'] as $revision => $label) {
            $version = new ReferenceDocumentVersion();
            $version->setReferenceDocument($scenarios);
            $version->setVersionLabel($label);
            $version->setPath($this->storePdf('Scenarios and Procedures ' . $label));
            $version->setUploadedBy($uploader);
            $version->setUploadedAt(new DateTime('-' . (4 - $revision) . ' weeks'));

            $manager->persist($version);
            $manager->flush();

            $scenariosVersions[] = $version;
        }

        $this->addReference(
            'reference-document-scenarios',
            $scenarios,
        );

        $definitions = new ReferenceDocument();
        $definitions->setName('Financial Definition List');
        $manager->persist($definitions);

        $definitionsVersion = new ReferenceDocumentVersion();
        $definitionsVersion->setReferenceDocument($definitions);
        $definitionsVersion->setVersionLabel('v1.0');
        $definitionsVersion->setPath($this->storePdf('Financial Definition List v1.0'));
        $definitionsVersion->setUploadedBy($uploader);
        $definitionsVersion->setUploadedAt(new DateTime('-8 weeks'));

        $manager->persist($definitionsVersion);
        $manager->flush();

        $this->addReference(
            'reference-document-definitions',
            $definitions,
        );

        // ALV-0 shipped the original version and stays pinned to it; the newer meetings follow the latest.
        $this->createSelection(
            $manager,
            'meeting-ALV-0',
            $scenarios,
            $scenariosVersions[0],
        );
        $this->createSelection(
            $manager,
            'meeting-ALV-1',
            $scenarios,
            null,
        );
        $this->createSelection(
            $manager,
            'meeting-ALV-3',
            $scenarios,
            null,
        );
        $this->createSelection(
            $manager,
            'meeting-ALV-3',
            $definitions,
            null,
        );

        $manager->flush();
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

    private function createSelection(
        ObjectManager $manager,
        string $meetingReference,
        ReferenceDocument $document,
        ?ReferenceDocumentVersion $pinnedVersion,
    ): void {
        $selection = new MeetingReferenceSelection();
        $selection->setMeeting($this->getReference(
            $meetingReference,
            Meeting::class,
        ));
        $selection->setReferenceDocument($document);
        $selection->setPinnedVersion($pinnedVersion);

        $manager->persist($selection);
    }

    private function storePdf(string $marker): string
    {
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
                StorageNamespace::ReferenceDocument,
                $temporaryFile,
            )->path;
        } finally {
            unlink($temporaryFile);
        }
    }
}
