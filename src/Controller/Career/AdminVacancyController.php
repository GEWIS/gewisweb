<?php

declare(strict_types=1);

namespace App\Controller\Career;

use App\Entity\Application\Enums\AlertTypes;
use App\Entity\Application\Enums\ReviseRefusal;
use App\Entity\Career\CareerLocalisedText;
use App\Entity\Career\Enums\VacancyCategories;
use App\Entity\Career\Vacancy;
use App\Entity\Career\VacancyRevision;
use App\Entity\User\Enums\UserRoles;
use App\Entity\User\User;
use App\Form\Career\VacancyType;
use App\Repository\Career\VacancyRevisionCommentRepository;
use App\Security\Application\RevisionVoter;
use App\Service\Application\EditLockService;
use App\Service\Application\RevisionReviser;
use App\Service\Career\CareerOverviewCountsProvider;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Vacancies across every company, from the board's side. Like a company profile, a vacancy is revised rather than
 * edited once it has been approved, so what is public only changes when somebody agrees to it.
 */
#[IsGranted(
    attribute: UserRoles::CompanyAdmin->value,
    message: 'You are not allowed to administer companies.',
)]
#[Route(
    path: '/admin/career/vacancies',
    name: 'admin/career/vacancies/',
)]
class AdminVacancyController extends AbstractController
{
    public function __construct(
        private readonly CareerOverviewCountsProvider $overviewCounts,
        private readonly VacancyRevisionCommentRepository $commentRepository,
        private readonly EditLockService $editLockService,
        private readonly RevisionReviser $reviser,
        private readonly EntityManagerInterface $entityManager,
        private readonly TranslatorInterface $translator,
    ) {
    }

    #[Route(
        path: '',
        name: 'index',
    )]
    public function index(): Response
    {
        return $this->render(
            'career/admin/vacancies/index.html.twig',
            ['counts' => $this->overviewCounts->counts()],
        );
    }

    #[Route(
        path: '/create',
        name: 'create',
        methods: [
            'GET',
            'POST',
        ],
    )]
    public function create(
        Request $request,
        #[CurrentUser]
        User $user,
    ): Response {
        $vacancy = new Vacancy();
        $vacancy->setPublished(true);

        $revision = $this->newDraftRevision();
        $revision->setAuthor($user->getMember());
        $vacancy->addRevision($revision);
        $vacancy->setCurrentRevision($revision);

        $form = $this->createForm(
            VacancyType::class,
            $vacancy,
            ['admin' => true],
        )->handleRequest($request);

        if (
            !$form->isSubmitted()
            || !$form->isValid()
        ) {
            return $this->render(
                'career/admin/vacancies/create.html.twig',
                ['form' => $form],
            );
        }

        $this->entityManager->persist($vacancy);
        $this->entityManager->persist($revision);
        $this->entityManager->flush();

        $this->addFlash(
            AlertTypes::Success->value,
            $this->translator->trans('The vacancy was saved as a draft. Submit it for review when you are ready.'),
        );

        return $this->redirectToRoute('admin/career/vacancies/index');
    }

    #[Route(
        path: '/{vacancy}/edit',
        name: 'edit',
        requirements: ['vacancy' => '\d+'],
        methods: [
            'GET',
            'POST',
        ],
    )]
    public function edit(
        Request $request,
        Vacancy $vacancy,
        #[CurrentUser]
        User $user,
    ): Response {
        $this->denyAccessUnlessGranted(
            RevisionVoter::SUBMIT,
            $vacancy,
        );

        $current = $vacancy->getCurrentRevision();
        if (null === $current) {
            throw $this->createNotFoundException();
        }

        if (!$current->getStatus()->isEditableByAuthor()) {
            $this->addFlash(
                AlertTypes::Warning->value,
                $this->translator->trans('This vacancy is not a draft. Revise it to start a new one.'),
            );

            return $this->redirectToRoute('admin/career/vacancies/index');
        }

        $lock = $this->editLockService->acquire(
            $vacancy,
            $user,
            $request->query->getBoolean('take') && $this->isGranted(UserRoles::Board->value),
        );
        if (null === $lock) {
            return $this->render(
                'career/admin/vacancies/edit-locked.html.twig',
                [
                    'vacancy' => $vacancy,
                    'lock' => $this->editLockService->blockingLock(
                        $vacancy,
                        $user,
                    ),
                ],
            );
        }

        // A vacancy belongs to whichever company sold the package it hangs off, so leaving the choice open would let
        // an edit hand the posting to somebody else. Creating one is where that choice is actually made.
        $form = $this->createForm(
            VacancyType::class,
            $vacancy,
            [
                'admin' => true,
                'company' => $vacancy->getCompany(),
            ],
        )->handleRequest($request);

        if (
            !$form->isSubmitted()
            || !$form->isValid()
        ) {
            return $this->render(
                'career/admin/vacancies/edit.html.twig',
                [
                    'form' => $form,
                    'vacancy' => $vacancy,
                    'comments' => $this->commentRepository->findThreadForVacancy($vacancy),
                ],
            );
        }

        $current->setLastEditedBy($user);
        $this->entityManager->flush();
        $this->editLockService->release(
            $vacancy,
            $user,
        );

        $this->addFlash(
            AlertTypes::Success->value,
            $this->translator->trans('Changes saved. Submit the revision for review when you are ready.'),
        );

        return $this->redirectToRoute('admin/career/vacancies/index');
    }

    #[Route(
        path: '/{vacancy}/revise',
        name: 'revise',
        requirements: ['vacancy' => '\d+'],
        methods: ['POST'],
    )]
    #[IsCsrfTokenValid(
        id: new Expression('"vacancy_revise-" ~ args["vacancy"].getId()'),
        tokenKey: '_csrf_token',
    )]
    public function revise(
        Vacancy $vacancy,
        #[CurrentUser]
        User $user,
    ): Response {
        $this->denyAccessUnlessGranted(
            RevisionVoter::SUBMIT,
            $vacancy,
        );

        $current = $vacancy->getCurrentRevision();
        if (null === $current) {
            throw $this->createNotFoundException();
        }

        $refusal = $current->getStatus()->reviseRefusal();
        if (null !== $refusal) {
            // trans() is called per arm (not around the match) so each literal stays statically extractable.
            $this->addFlash(
                AlertTypes::Warning->value,
                match ($refusal) {
                    ReviseRefusal::AlreadyADraft => $this->translator->trans('There is already a draft to work on.'),
                    ReviseRefusal::UnderReview => $this->translator->trans(
                        'This vacancy is with the committee and cannot be revised until they have decided.',
                    ),
                    ReviseRefusal::Closed => $this->translator->trans(
                        'This vacancy was closed by the board and can no longer be revised.',
                    ),
                },
            );

            // The draft that is already there is what the reader wants; anything else has nothing to edit yet.
            return ReviseRefusal::AlreadyADraft === $refusal
                ? $this->backToEdit($vacancy)
                : $this->redirectToRoute('admin/career/vacancies/index');
        }

        $draft = $this->reviser->spawnDraft(
            $current,
            $user,
        );
        $this->entityManager->persist($draft);
        $this->entityManager->flush();

        $this->addFlash(
            AlertTypes::Success->value,
            $this->translator->trans('A new draft was created. Edit it and submit it for review.'),
        );

        return $this->backToEdit($vacancy);
    }

    private function backToEdit(Vacancy $vacancy): Response
    {
        return $this->redirectToRoute(
            'admin/career/vacancies/edit',
            ['vacancy' => $vacancy->getId()],
        );
    }

    /**
     * A blank draft revision with its localised texts initialised, so the create form can bind to it.
     */
    private function newDraftRevision(): VacancyRevision
    {
        $revision = new VacancyRevision();
        $revision->setName(new CareerLocalisedText(
            null,
            null,
        ));
        $revision->setLocation(new CareerLocalisedText(
            null,
            null,
        ));
        $revision->setWebsite(new CareerLocalisedText(
            null,
            null,
        ));
        $revision->setDescription(new CareerLocalisedText(
            null,
            null,
        ));
        $revision->setAttachment(new CareerLocalisedText(
            null,
            null,
        ));
        $revision->setCategory(VacancyCategories::Jobs);

        return $revision;
    }
}
