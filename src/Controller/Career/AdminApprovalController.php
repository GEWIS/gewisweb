<?php

declare(strict_types=1);

namespace App\Controller\Career;

use App\Controller\Application\AbstractRevisionReviewController;
use App\Entity\Application\RevisionInterface;
use App\Entity\Career\CompanyRevision;
use App\Entity\Career\VacancyRevision;
use App\Entity\User\Enums\UserRoles;
use App\Entity\User\User;
use App\Repository\Career\CompanyPackageRepository;
use App\Repository\Career\CompanyRevisionCommentRepository;
use App\Repository\Career\CompanyRevisionRepository;
use App\Repository\Career\VacancyRevisionCommentRepository;
use App\Repository\Career\VacancyRevisionRepository;
use App\ViewModel\Application\ReviewQueueRow;
use App\ViewModel\Application\RevisionActions;
use Override;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\Component\Security\Http\Attribute\IsGranted;

use function assert;

/**
 * The review surface for the career module: one queue holding both the company profiles and the vacancies waiting for
 * the committee, and a per-revision screen showing what changed against the revision before it, the discussion, and
 * whichever transitions the workflow allows the person looking at it.
 *
 * Everything here is the committee's; a company follows its own proposal through the portal instead. Which buttons
 * appear is still left to the workflow guards rather than decided here, so the screen does not have to tell a
 * reviewer apart from an author.
 */
#[IsGranted(
    attribute: UserRoles::CompanyAdmin->value,
    message: 'You are not allowed to administer companies.',
)]
#[Route(
    path: '/admin/career/approvals',
    name: 'admin/career/approvals/',
)]
class AdminApprovalController extends AbstractRevisionReviewController
{
    public function __construct(
        private readonly CompanyRevisionRepository $companyRevisionRepository,
        private readonly VacancyRevisionRepository $vacancyRevisionRepository,
        private readonly CompanyRevisionCommentRepository $companyCommentRepository,
        private readonly VacancyRevisionCommentRepository $vacancyCommentRepository,
        private readonly CompanyPackageRepository $packageRepository,
    ) {
    }

    #[Route(
        path: '',
        name: 'index',
    )]
    public function index(): Response
    {
        return $this->render(
            'career/admin/approvals/index.html.twig',
            [
                'companyRows' => ReviewQueueRow::fromRevisions(
                    $this->companyRevisionRepository->findForReview(),
                    static function (RevisionInterface $revision): string {
                        assert($revision instanceof CompanyRevision);

                        return $revision->getCompany()->getName();
                    },
                    'admin/career/approvals/company',
                ),
                'vacancyRows' => ReviewQueueRow::fromRevisions(
                    $this->vacancyRevisionRepository->findForReview(),
                    static function (RevisionInterface $revision): string {
                        assert($revision instanceof VacancyRevision);

                        return $revision->getVacancy()->getSlugName();
                    },
                    'admin/career/approvals/vacancy',
                ),
                'pendingBanners' => $this->packageRepository->findPendingBanners(),
            ],
        );
    }

    #[Route(
        path: '/company/{revision}',
        name: 'company',
        requirements: ['revision' => '\d+'],
    )]
    public function reviewCompany(CompanyRevision $revision): Response
    {
        $this->assertMayReview($revision);

        return $this->renderReview($revision);
    }

    #[Route(
        path: '/vacancy/{revision}',
        name: 'vacancy',
        requirements: ['revision' => '\d+'],
    )]
    public function reviewVacancy(VacancyRevision $revision): Response
    {
        $this->assertMayReview($revision);

        return $this->renderReview($revision);
    }

    #[Route(
        path: '/company/{revision}/decide',
        name: 'company/decide',
        requirements: ['revision' => '\d+'],
        methods: ['POST'],
    )]
    public function decideCompany(
        Request $request,
        CompanyRevision $revision,
        #[CurrentUser]
        User $user,
    ): Response {
        return $this->handleDecision(
            $request,
            $revision,
            $user,
        );
    }

    #[Route(
        path: '/vacancy/{revision}/decide',
        name: 'vacancy/decide',
        requirements: ['revision' => '\d+'],
        methods: ['POST'],
    )]
    public function decideVacancy(
        Request $request,
        VacancyRevision $revision,
        #[CurrentUser]
        User $user,
    ): Response {
        return $this->handleDecision(
            $request,
            $revision,
            $user,
        );
    }

    #[Route(
        path: '/company/{revision}/comment',
        name: 'company/comment',
        requirements: ['revision' => '\d+'],
        methods: ['POST'],
    )]
    #[IsCsrfTokenValid(
        id: new Expression('"career_review_comment-" ~ args["revision"].getId()'),
        tokenKey: '_csrf_token',
    )]
    public function commentCompany(
        Request $request,
        CompanyRevision $revision,
        #[CurrentUser]
        User $user,
    ): Response {
        return $this->comment(
            $request,
            $revision,
            $user,
        );
    }

    #[Route(
        path: '/vacancy/{revision}/comment',
        name: 'vacancy/comment',
        requirements: ['revision' => '\d+'],
        methods: ['POST'],
    )]
    #[IsCsrfTokenValid(
        id: new Expression('"career_review_comment-" ~ args["revision"].getId()'),
        tokenKey: '_csrf_token',
    )]
    public function commentVacancy(
        Request $request,
        VacancyRevision $revision,
        #[CurrentUser]
        User $user,
    ): Response {
        return $this->comment(
            $request,
            $revision,
            $user,
        );
    }

    #[Route(
        path: '/company/{revision}/discard',
        name: 'company/discard',
        requirements: ['revision' => '\d+'],
        methods: ['POST'],
    )]
    #[IsCsrfTokenValid(
        id: new Expression('"career_discard-" ~ args["revision"].getId()'),
        tokenKey: '_csrf_token',
    )]
    public function discardCompany(CompanyRevision $revision): Response
    {
        return $this->discardDraft(
            $revision,
            'admin/career/companies/view',
            ['company' => $revision->getCompany()->getId()],
        );
    }

    #[Route(
        path: '/vacancy/{revision}/discard',
        name: 'vacancy/discard',
        requirements: ['revision' => '\d+'],
        methods: ['POST'],
    )]
    #[IsCsrfTokenValid(
        id: new Expression('"career_discard-" ~ args["revision"].getId()'),
        tokenKey: '_csrf_token',
    )]
    public function discardVacancy(VacancyRevision $revision): Response
    {
        return $this->discardDraft(
            $revision,
            'admin/career/vacancies/index',
        );
    }

    private function comment(
        Request $request,
        CompanyRevision|VacancyRevision $revision,
        User $user,
    ): Response {
        $this->handleCommentPost(
            $request,
            $revision,
            $user,
        );

        return $this->reviewResponse($revision);
    }

    #[Override]
    protected function reviewTemplate(): string
    {
        return 'career/admin/approvals/review.html.twig';
    }

    /**
     * trans() is called per arm (not around the match) so each literal stays statically extractable.
     */
    #[Override]
    protected function decisionFlash(string $transition): string
    {
        return match ($transition) {
            'submit' => $this->translator->trans('Submitted for review.'),
            'start_review' => $this->translator->trans('Review started.'),
            default => $this->translator->trans('The revision was updated.'),
        };
    }

    /**
     * Starting a review stays on the screen so the committee can decide straight away; every other decision returns
     * to the queue.
     */
    #[Override]
    protected function decisionResponse(
        RevisionInterface $revision,
        string $transition,
    ): Response {
        return 'start_review' === $transition
            ? $this->reviewResponse($revision)
            : $this->redirectToRoute('admin/career/approvals/index');
    }

    /**
     * Both career aggregates share one screen, so the branch that used to sit inside the template's context array
     * lives here: which subject is named, whose thread is shown, and which of the two route families the decision,
     * comment and discard forms post to.
     *
     * @return array<string, mixed>
     */
    #[Override]
    protected function reviewContext(
        RevisionInterface $revision,
        RevisionActions $actions,
    ): array {
        if ($revision instanceof CompanyRevision) {
            $company = $revision->getCompany();
            $subjectName = $company->getName();
            $comments = $this->companyCommentRepository->findThreadForCompany($company);
        } else {
            assert($revision instanceof VacancyRevision);
            $vacancy = $revision->getVacancy();
            $subjectName = $vacancy->getSlugName();
            $comments = $this->vacancyCommentRepository->findThreadForVacancy($vacancy);
        }

        $prefix = $this->reviewRoute($revision);

        return [
            'subjectName' => $subjectName,
            'comments' => $comments,
            'decideRoute' => $prefix . '/decide',
            'commentRoute' => $prefix . '/comment',
            'discardRoute' => $prefix . '/discard',
        ];
    }

    #[Override]
    protected function reviewResponse(RevisionInterface $revision): Response
    {
        return $this->redirectToRoute(
            $this->reviewRoute($revision),
            ['revision' => $revision->getId()],
        );
    }

    private function reviewRoute(RevisionInterface $revision): string
    {
        return $revision instanceof CompanyRevision
            ? 'admin/career/approvals/company'
            : 'admin/career/approvals/vacancy';
    }
}
