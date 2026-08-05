<?php

declare(strict_types=1);

namespace App\Tests\Security\Education;

use App\Entity\Education\CourseDocument;
use App\Entity\Education\CourseDocumentPage;
use App\Entity\Education\Enums\DocumentFlattenStatus;
use App\Entity\Education\Enums\ExamTypes;
use App\Entity\Education\Exam;
use App\Entity\User\Enums\UserRoles;
use App\Security\Education\CourseDocumentVoter;
use App\Service\Education\CampusNetworkChecker;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\NullToken;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * Who may download course material: a logged-in member from anywhere, or anyone from the TU/e campus network. That is
 * the arrangement the department shares its exams under, so the matrix is pinned rather than left to two conditions in
 * a method.
 *
 * A company user is deliberately not a member here: being logged in as a corporate account says nothing about being
 * entitled to course material.
 */
final class CourseDocumentVoterTest extends TestCase
{
    private const array RANGES = ['131.155.0.0/16'];

    public function testAMemberMayDownloadFromAnywhere(): void
    {
        self::assertSame(
            VoterInterface::ACCESS_GRANTED,
            $this->vote(
                member: true,
                clientIp: '8.8.8.8',
            ),
        );
    }

    public function testAnyoneOnCampusMayDownload(): void
    {
        self::assertSame(
            VoterInterface::ACCESS_GRANTED,
            $this->vote(
                member: false,
                clientIp: '131.155.10.7',
            ),
        );
    }

    public function testAGuestOffCampusMayNot(): void
    {
        self::assertSame(
            VoterInterface::ACCESS_DENIED,
            $this->vote(
                member: false,
                clientIp: '8.8.8.8',
            ),
        );
    }

    /**
     * A download is rebuilt from the document's rendered pages, so until it has been rasterized there is nothing to
     * hand over, whoever is asking.
     */
    public function testNobodyMayDownloadADocumentThatHasNotBeenProcessed(): void
    {
        self::assertSame(
            VoterInterface::ACCESS_DENIED,
            $this->vote(
                member: true,
                clientIp: '131.155.10.7',
                downloadable: false,
            ),
        );
    }

    public function testItIgnoresOtherAttributesAndSubjects(): void
    {
        self::assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter(
                member: true,
                clientIp: '131.155.10.7',
            )->vote(
                new NullToken(),
                $this->document(true),
                ['SOMETHING_ELSE'],
            ),
        );
        self::assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter(
                member: true,
                clientIp: '131.155.10.7',
            )->vote(
                new NullToken(),
                new stdClass(),
                [CourseDocumentVoter::DOWNLOAD],
            ),
        );
    }

    private function vote(
        bool $member,
        string $clientIp,
        bool $downloadable = true,
    ): int {
        return $this->voter(
            $member,
            $clientIp,
        )->vote(
            new NullToken(),
            $this->document($downloadable),
            [CourseDocumentVoter::DOWNLOAD],
        );
    }

    private function voter(
        bool $member,
        string $clientIp,
    ): CourseDocumentVoter {
        $security = self::createStub(Security::class);
        $security->method('isGranted')->willReturnCallback(
            static fn (mixed $attribute): bool => $member
                && UserRoles::User->value === $attribute,
        );

        $stack = new RequestStack();
        $stack->push(Request::create(
            '/education',
            server: ['REMOTE_ADDR' => $clientIp],
        ));

        return new CourseDocumentVoter(
            $security,
            new CampusNetworkChecker(
                $stack,
                self::RANGES,
            ),
        );
    }

    private function document(bool $downloadable): CourseDocument
    {
        $document = new Exam();
        $document->setExamType(ExamTypes::Final);
        $document->setFlattenStatus(
            $downloadable ? DocumentFlattenStatus::Ready : DocumentFlattenStatus::Pending,
        );

        if ($downloadable) {
            $document->addPage(new CourseDocumentPage());
        }

        return $document;
    }
}
