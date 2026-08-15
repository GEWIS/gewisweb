<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Frontpage;

use App\Controller\Frontpage\AdminPollApprovalController;
use App\Entity\Application\Enums\RevisionStatus;
use App\Entity\Frontpage\PollRevision;
use App\Entity\User\User;
use App\Security\User\SudoMode;
use App\Tests\Integration\DatabaseTestCase;
use DateTime;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Workflow\Registry;

use function strval;

/**
 * Approving a poll also schedules it, so the poll's decision form carries a closing date next to the approve button.
 * These pin that the date has to be a real date in the future, and that a decision that is refused leaves the poll
 * exactly as it was.
 *
 * The actions are invoked directly with the current user on the token storage, as the other approval tests do and for
 * the same reason: the session guard force-logs-out any session with no managed-session row behind it.
 */
final class AdminPollApprovalControllerTest extends DatabaseTestCase
{
    public function testApprovingWithAFutureDateSchedulesThePoll(): void
    {
        $revision = $this->aQuestionWaitingOnTheBoard();
        $closesOn = new DateTime('+10 days');

        $this->decide(
            $revision,
            'approve',
            $closesOn->format('Y-m-d'),
        );

        self::assertSame(
            RevisionStatus::Approved,
            $revision->getStatus(),
        );
        self::assertSame(
            $revision,
            $revision->getPoll()->getLiveRevision(),
        );
        self::assertSame(
            $closesOn->format('Y-m-d'),
            $revision->getPoll()->getExpiryDate()?->format('Y-m-d'),
        );
        self::assertTrue($revision->getPoll()->isActive());
    }

    public function testApprovingWithoutADateIsRefused(): void
    {
        $this->assertApprovalIsRefused(
            '',
            'Fill in a closing date before approving this poll.',
        );
    }

    /**
     * A poll closes on its date, so today would publish one that is already over.
     */
    public function testApprovingWithTodayIsRefused(): void
    {
        $this->assertApprovalIsRefused(
            new DateTime('today')->format('Y-m-d'),
            'The closing date must be in the future.',
        );
    }

    public function testApprovingWithAPastDateIsRefused(): void
    {
        $this->assertApprovalIsRefused(
            new DateTime('-1 week')->format('Y-m-d'),
            'The closing date must be in the future.',
        );
    }

    public function testApprovingWithSomethingThatIsNotADateIsRefused(): void
    {
        $this->assertApprovalIsRefused(
            'soon',
            'Please enter a valid date.',
        );
    }

    /**
     * Turning a question down needs no date, but it does need a reason: the shared decision form makes feedback
     * mandatory, so an empty box comes back with the screen instead of a decision.
     */
    public function testRejectingWithoutAReasonComesBackWithTheScreen(): void
    {
        $revision = $this->aQuestionWaitingOnTheBoard();

        $response = $this->decide(
            $revision,
            'reject',
        );

        self::assertSame(
            RevisionStatus::InReview,
            $revision->getStatus(),
        );
        self::assertStringContainsString(
            'Feedback',
            strval($response->getContent()),
        );
    }

    public function testRejectingWithAReasonTurnsTheQuestionDown(): void
    {
        $revision = $this->aQuestionWaitingOnTheBoard();

        $this->decide(
            $revision,
            'reject',
            message: 'Ask something the association can act on.',
        );

        self::assertSame(
            RevisionStatus::Rejected,
            $revision->getStatus(),
        );
        self::assertNull($revision->getPoll()->getLiveRevision());
    }

    /**
     * The refusal comes back as the review screen with the reason on the date field, the way any other form error
     * returns.
     */
    private function assertApprovalIsRefused(
        string $expiryDate,
        string $refusal,
    ): void {
        $revision = $this->aQuestionWaitingOnTheBoard();

        $response = $this->decide(
            $revision,
            'approve',
            $expiryDate,
        );

        self::assertSame(
            RevisionStatus::InReview,
            $revision->getStatus(),
        );
        self::assertNull($revision->getPoll()->getLiveRevision());
        self::assertNull($revision->getPoll()->getExpiryDate());
        self::assertStringContainsString(
            $refusal,
            strval($response->getContent()),
        );
    }

    /**
     * A decision as the review screen posts it: the pressed button names the transition, and the closing date is a
     * field of the same form. The board claims the question for review first, which is what opens the decisions at
     * all.
     */
    private function decide(
        PollRevision $revision,
        string $transition,
        ?string $expiryDate = null,
        string $message = '',
    ): Response {
        $request = $this->decisionRequest(
            $transition,
            $expiryDate,
            $message,
        );
        $this->authenticateAsBoardWithSudo($request);

        self::getContainer()->get(Registry::class)->get(
            $revision,
            'revision',
        )->apply(
            $revision,
            'start_review',
        );
        $this->entityManager->flush();

        return $this->controller()->decide(
            $request,
            $revision,
            $this->board(),
        );
    }

    /**
     * CSRF is stateless here, so a same-origin request naming the double-submit cookie is what the manager accepts,
     * exactly as a browser posting a form on one of our own pages does.
     */
    private function decisionRequest(
        string $transition,
        ?string $expiryDate,
        string $message,
    ): Request {
        $parameters = [
            'review_decision' => [
                $transition => '',
                '_csrf_token' => 'csrf-token',
            ],
        ];

        if ('' !== $message) {
            $parameters['review_decision']['message'] = $message;
        }

        if (null !== $expiryDate) {
            $parameters['review_decision']['expiryDate'] = $expiryDate;
        }

        $request = new Request(
            request: $parameters,
            server: ['HTTP_SEC_FETCH_SITE' => 'same-origin'],
        );
        $request->setMethod(Request::METHOD_POST);

        return $request;
    }

    private function controller(): AdminPollApprovalController
    {
        return self::getContainer()->get(AdminPollApprovalController::class);
    }

    private function aQuestionWaitingOnTheBoard(): PollRevision
    {
        $revision = $this->entityManager->createQueryBuilder()
            ->select('r')
            ->from(
                PollRevision::class,
                'r',
            )
            ->where('r.status = :submitted')
            ->setParameter(
                'submitted',
                RevisionStatus::Submitted->value,
            )
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
        self::assertInstanceOf(
            PollRevision::class,
            $revision,
            'The seed is expected to contain a poll waiting on the board.',
        );

        return $revision;
    }

    private function board(): User
    {
        $user = $this->entityManager->getRepository(User::class)->find(8000);
        self::assertInstanceOf(
            User::class,
            $user,
        );

        return $user;
    }

    /**
     * The request the decision arrives on is the one the CSRF check and the sudo grant both read, so it is the one
     * that goes on the stack.
     */
    private function authenticateAsBoardWithSudo(Request $request): void
    {
        self::getContainer()->get('security.token_storage')->setToken(new UsernamePasswordToken(
            $this->board(),
            'main',
            ['ROLE_BOARD'],
        ));

        $session = self::getContainer()->get('session.factory')->createSession();
        $request->setSession($session);
        // A sudo grant is only read back off a session the request already carried, so the cookie has to be there.
        $request->cookies->set(
            $session->getName(),
            'test',
        );
        self::getContainer()->get('request_stack')->push($request);

        self::getContainer()->get(SudoMode::class)->grant();
    }
}
