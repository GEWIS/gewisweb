<?php

declare(strict_types=1);

namespace App\Tests\Integration\LiveComponent\Decision\Admin;

use App\Entity\Decision\Enums\MeetingTypes;
use App\Entity\Decision\Meeting;
use App\Entity\Decision\MeetingDocument;
use App\Entity\Decision\MeetingMinutes;
use App\Entity\Decision\MeetingPoint;
use App\Entity\Decision\ReferenceDocument;
use App\Entity\User\User;
use App\Security\User\SudoMode;
use App\Tests\Integration\DatabaseTestCase;
use App\Twig\Components\Decision\Admin\MeetingManage;
use App\ViewModel\Decision\MeetingPointView;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use function array_map;
use function array_reverse;
use function count;

/**
 * Exercises the meeting management component as the framework does (the real instance with its real services) after
 * authenticating on the token storage, mirroring the sign-up overview test: the live HTTP endpoint is not viable
 * because the session guard force-logs-out synthetic sessions.
 */
final class MeetingManageTest extends DatabaseTestCase
{
    public function testAddPointAppendsAnEmptyPoint(): void
    {
        $this->authenticate();
        $component = $this->manageFor();

        $before = $component->getView()->points;
        $component->addPoint();

        $points = $component->getView()->points;
        self::assertCount(
            count($before) + 1,
            $points,
        );
        $added = $points[count($before)]->point;
        self::assertSame(
            '',
            $added->getNumber(),
        );
        self::assertNotNull($component->savedAt);
    }

    public function testSyncEditsAppliesPendingPointAndDocumentEdits(): void
    {
        $this->authenticate();
        $component = $this->manageFor();

        $point = $this->point('2');
        $document = $component->getView()->points[0]->documents[0];

        $component->pointEdits = [
            (string) $point->getId() => [
                'number' => '9',
                'title' => 'Renumbered',
            ],
        ];
        $component->documentEdits = [
            (string) $document->getId() => ['name' => 'Agenda (final)'],
        ];
        $component->syncEdits();

        self::assertSame(
            '9',
            $point->getNumber(),
        );
        self::assertSame(
            'Renumbered',
            $point->getTitle(),
        );
        self::assertSame(
            'Agenda (final)',
            $document->getName(),
        );
        self::assertSame(
            [],
            $component->pointEdits,
        );
        self::assertNotNull($component->savedAt);
    }

    public function testDeletePointMovesItsDocumentsToTheMeetingLevelGroup(): void
    {
        $this->authenticate();
        $component = $this->manageFor();

        $point = $this->point('2');
        $component->deletePoint((int) $point->getId());

        $view = $component->getView();
        $names = array_map(
            static fn (MeetingDocument $document) => $document->getName(),
            $view->meetingLevelDocuments,
        );
        self::assertContains(
            'Agenda',
            $names,
        );
        self::assertCount(
            3,
            $view->points,
        );
    }

    public function testDeleteDocumentRemovesItWithItsVersions(): void
    {
        $this->authenticate();
        $component = $this->manageFor();

        $document = $component->getView()->meetingLevelDocuments[0];
        $component->deleteDocument((int) $document->getId());

        self::assertSame(
            [],
            $component->getView()->meetingLevelDocuments,
        );
    }

    public function testReorderPointsPersistsTheNewOrder(): void
    {
        $this->authenticate();
        $component = $this->manageFor();

        $ids = array_map(
            static fn (MeetingPointView $pointView) => (int) $pointView->point->getId(),
            $component->getView()->points,
        );
        $component->reorderPoints(array_reverse($ids));

        self::assertSame(
            array_reverse($ids),
            array_map(
                static fn (MeetingPointView $pointView) => (int) $pointView->point->getId(),
                $component->getView()->points,
            ),
        );
    }

    public function testDeleteMinutesRemovesTheMinutes(): void
    {
        $this->authenticate();
        $component = $this->manageFor();

        self::assertNotNull($component->getView()->minutes);
        $component->deleteMinutes();

        self::assertNull($component->getView()->minutes);
    }

    public function testToggleReferenceSelectsAndDeselectsALibraryDocument(): void
    {
        $this->authenticate();
        $component = $this->manageFor();

        $definitions = $this->referenceDocument('Financial Definition List');
        self::assertCount(
            1,
            $component->getView()->references,
        );

        $component->toggleReference((int) $definitions->getId());
        $references = $component->getView()->references;
        self::assertCount(
            2,
            $references,
        );

        // A fresh selection pins the latest version explicitly; nothing ever follows the library implicitly.
        foreach ($references as $selection) {
            if ($selection->getReferenceDocument() !== $definitions) {
                continue;
            }

            self::assertSame(
                $definitions->getLatestVersion(),
                $selection->getPinnedVersion(),
            );
        }

        $component->toggleReference((int) $definitions->getId());
        self::assertCount(
            1,
            $component->getView()->references,
        );
    }

    public function testCarryOverCopiesTheSelectionOfThePreviousMeeting(): void
    {
        $this->authenticate();
        $component = $this->manageFor(3);

        self::assertSame(
            [],
            $component->getView()->references,
        );

        $component->carryOver();

        $references = $component->getView()->references;
        self::assertCount(
            2,
            $references,
        );
        $names = array_map(
            static fn ($selection) => $selection->getReferenceDocument()->getName(),
            $references,
        );
        self::assertContains(
            'Scenarios and Procedures',
            $names,
        );
        self::assertContains(
            'Financial Definition List',
            $names,
        );
    }

    public function testPendingPinsAreAppliedBeforeRendering(): void
    {
        $this->authenticate();
        $component = $this->manageFor(1);

        $scenarios = $this->referenceDocument('Scenarios and Procedures');
        $original = $scenarios->getVersions()->first();
        self::assertNotFalse($original);

        $component->pins = [(string) $scenarios->getId() => (string) $original->getId()];
        $component->syncEdits();

        $selection = $component->getView()->references[0];
        self::assertSame(
            'v3.0',
            $selection->getPinnedVersion()->getVersionLabel(),
        );
    }

    public function testDetailsEditsPersistTheTimeAndPlace(): void
    {
        $this->authenticate();
        $component = $this->manageFor(1);

        self::assertNull($component->getView()->localDetails);

        $component->details = [
            'startTime' => '20:00',
            'location' => 'Auditorium 4',
        ];
        $component->syncEdits();

        $details = $component->getView()->localDetails;
        self::assertNotNull($details);
        self::assertSame(
            '20:00',
            $details->getStartTime()?->format('H:i'),
        );
        self::assertSame(
            'Auditorium 4',
            $details->getLocation(),
        );
        self::assertNotNull($component->savedAt);
    }

    public function testActionsRequireTheBoardRole(): void
    {
        $this->authenticate(['ROLE_USER']);
        $component = $this->manageFor();

        $this->expectException(AccessDeniedException::class);
        $component->addPoint();
    }

    public function testActionsRequireSudoMode(): void
    {
        $this->authenticate(
            ['ROLE_BOARD'],
            sudo: false,
        );
        $component = $this->manageFor();

        $this->expectException(AccessDeniedException::class);
        $component->addPoint();
    }

    /**
     * @param list<string> $roles
     */
    private function authenticate(
        array $roles = ['ROLE_BOARD'],
        bool $sudo = true,
    ): void {
        $user = $this->entityManager->getRepository(User::class)->find(8000);
        self::assertInstanceOf(
            User::class,
            $user,
        );

        self::getContainer()->get('security.token_storage')->setToken(new UsernamePasswordToken(
            $user,
            'main',
            $roles,
        ));

        $session = self::getContainer()->get('session.factory')->createSession();
        $request = new Request();
        $request->setSession($session);
        $request->cookies->set(
            $session->getName(),
            $session->getId(),
        );
        self::getContainer()->get('request_stack')->push($request);

        if (!$sudo) {
            return;
        }

        self::getContainer()->get(SudoMode::class)->grant();
    }

    /**
     * The complete GMM by default; positive offsets walk forward through the sequentially numbered GMMs (+1 the
     * processing one, +2 the soonest upcoming, +3 the one after).
     */
    private function manageFor(int $offset = 0): MeetingManage
    {
        $component = self::getContainer()->get(MeetingManage::class);
        $component->type = 'gmm';
        $component->number = $this->completeGmmNumber() + $offset;

        return $component;
    }

    private function completeGmmNumber(): int
    {
        $minutes = $this->entityManager->getRepository(MeetingMinutes::class)->findAll();
        self::assertCount(
            1,
            $minutes,
        );

        return $minutes[0]->getMeeting()->getNumber();
    }

    private function referenceDocument(string $name): ReferenceDocument
    {
        $document = $this->entityManager->getRepository(ReferenceDocument::class)->findOneBy(['name' => $name]);
        self::assertInstanceOf(
            ReferenceDocument::class,
            $document,
        );

        return $document;
    }

    private function point(string $number): MeetingPoint
    {
        $meeting = $this->entityManager->find(
            Meeting::class,
            [
                'type' => MeetingTypes::ALV,
                'number' => $this->completeGmmNumber(),
            ],
        );
        self::assertNotNull($meeting);

        $point = $this->entityManager->getRepository(MeetingPoint::class)->findOneBy([
            'meeting' => $meeting,
            'number' => $number,
        ]);
        self::assertInstanceOf(
            MeetingPoint::class,
            $point,
        );

        return $point;
    }
}
