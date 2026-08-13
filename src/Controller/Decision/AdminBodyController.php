<?php

declare(strict_types=1);

namespace App\Controller\Decision;

use App\Controller\Application\AbstractRevisionController;
use App\Controller\Application\HoldsEditLockTrait;
use App\Entity\Application\Enums\AlertTypes;
use App\Entity\Application\Enums\ReviseRefusal;
use App\Entity\Decision\Organ;
use App\Entity\Decision\OrganInformation;
use App\Entity\Decision\OrganInformationRevision;
use App\Entity\User\Enums\UserRoles;
use App\Entity\User\User;
use App\Form\Decision\OrganInformationRevisionType;
use App\Form\Decision\OrganInformationType;
use App\Repository\Decision\OrganInformationRevisionCommentRepository;
use App\Repository\Decision\OrganRepository;
use App\Security\Application\RevisionVoter;
use App\Service\Application\RevisionReviser;
use App\Service\Decision\OrganImageUploadService;
use App\ViewModel\Decision\BodyPageRow;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\Component\Security\Http\Attribute\IsGranted;

use function array_values;
use function is_array;
use function ksort;

/**
 * Where a body writes its own page. A body never edits what is on the website: it works on a draft, submits it, and the
 * board decides, which is what {@see AdminBodyApprovalController} is for.
 *
 * Which bodies somebody may write for is the organs they are installed in, and the board may write for all of them.
 * That is the voter's answer rather than this controller's, because the page names the body it belongs to and the voter
 * already reads organ membership off it.
 */
#[IsGranted(
    attribute: new Expression(
        'is_granted("' . UserRoles::ActiveMember->value . '") or is_granted("' . UserRoles::Board->value . '")',
    ),
    message: 'You are not allowed to administer bodies.',
)]
#[Route(
    path: '/admin/decision/bodies',
    name: 'admin/decision/bodies/',
)]
class AdminBodyController extends AbstractRevisionController
{
    use HoldsEditLockTrait;

    public function __construct(
        private readonly OrganRepository $organRepository,
        private readonly OrganInformationRevisionCommentRepository $commentRepository,
        private readonly OrganImageUploadService $imageUploadService,
        private readonly RevisionReviser $reviser,
    ) {
    }

    /**
     * The bodies this member may write for, with what is happening to each page.
     */
    #[Route(
        path: '',
        name: 'index',
    )]
    public function index(
        #[CurrentUser]
        User $user,
    ): Response {
        $organs = $this->editableOrgans($user);
        $this->organRepository->warmPageAssociations($organs);

        return $this->render(
            'decision/admin/bodies/index.html.twig',
            ['rows' => BodyPageRow::fromOrgans($organs)],
        );
    }

    /**
     * The page as it stands, with whatever the body may do to it next. A body with no page at all is offered the chance
     * to start one.
     */
    #[Route(
        path: '/{organ}',
        name: 'view',
        requirements: ['organ' => '\d+'],
    )]
    public function view(Organ $organ): Response
    {
        $page = $organ->getOrganInformation();

        if (null !== $page) {
            $this->denyAccessUnlessGranted(
                RevisionVoter::VIEW,
                $page,
            );
        } elseif (!$this->mayStartAPage($organ)) {
            throw $this->createAccessDeniedException('You are not allowed to write this body\'s page.');
        }

        return $this->render(
            'decision/admin/bodies/view.html.twig',
            [
                'organ' => $organ,
                // Never 'page': the base layout treats a defined `page` as a custom page and builds an hreflang link
                // out of it.
                'information' => $page,
                'revision' => $page?->getCurrentRevision(),
                'comments' => null === $page
                    ? []
                    : $this->commentRepository->findThreadForOrganInformation($page),
            ],
        );
    }

    #[Route(
        path: '/{organ}/edit',
        name: 'edit',
        requirements: ['organ' => '\d+'],
        methods: [
            'GET',
            'POST',
        ],
    )]
    public function edit(
        Request $request,
        Organ $organ,
        #[CurrentUser]
        User $user,
    ): Response {
        $page = $this->pageToEdit(
            $organ,
            $user,
        );
        $draft = $page->getCurrentRevision();

        if (
            null === $draft
            || !$draft->getStatus()->isEditableByAuthor()
        ) {
            $this->addFlash(
                AlertTypes::Warning->value,
                $this->translator->trans('This page is not a draft right now. Revise it to start a new one.'),
            );

            return $this->redirectToRoute(
                'admin/decision/bodies/view',
                ['organ' => $organ->getId()],
            );
        }

        if (
            null === $this->editLockService->acquire(
                $page,
                $user,
            )
        ) {
            return $this->render(
                'decision/admin/bodies/edit-locked.html.twig',
                [
                    'organ' => $organ,
                    'lock' => $this->editLockService->blockingLock(
                        $page,
                        $user,
                    ),
                ],
            );
        }

        $form = $this->createForm(
            OrganInformationType::class,
            $page,
        )->handleRequest($request);

        if (
            !$form->isSubmitted()
            || !$form->isValid()
        ) {
            return $this->render(
                'decision/admin/bodies/edit.html.twig',
                [
                    'form' => $form,
                    'organ' => $organ,
                    'information' => $page,
                    'revision' => $draft,
                    // The picker holds the frame to a minimum width of the original, which it cannot read off the
                    // rendition it draws on.
                    'bannerSourceWidth' => $this->imageUploadService->sourceWidth($draft->getBannerSource()),
                    'logoSourceWidth' => $this->imageUploadService->sourceWidth($draft->getLogoSource()),
                ],
            );
        }

        $stored = $this->storeImages(
            $form->get('currentRevision'),
            $draft,
        );

        $draft->setAuthor($user->getMember());
        $draft->setLastEditedBy($user);
        $this->entityManager->flush();
        $this->editLockService->release(
            $page,
            $user,
        );

        // The text is saved either way, so what went wrong is said rather than hidden behind the usual reassurance: a
        // body that is told its page is saved would submit it for review with the old image still on it.
        if ($stored) {
            $this->addFlash(
                AlertTypes::Success->value,
                $this->translator->trans('Your changes are saved. Submit them for review when you are ready.'),
            );
        } else {
            $this->addFlash(
                AlertTypes::Warning->value,
                $this->translator->trans(
                    'Your changes are saved, but an image could not be stored. Try uploading it again.',
                ),
            );
        }

        return $this->redirectToRoute(
            'admin/decision/bodies/view',
            ['organ' => $organ->getId()],
        );
    }

    #[Route(
        path: '/{organ}/edit/ping',
        name: 'edit_ping',
        requirements: ['organ' => '\d+'],
        methods: ['POST'],
    )]
    #[IsCsrfTokenValid(
        id: 'body_edit_lock',
        tokenKey: '_csrf_token',
    )]
    public function editPing(
        Organ $organ,
        #[CurrentUser]
        User $user,
    ): JsonResponse {
        $page = $organ->getOrganInformation();
        if (null === $page) {
            throw $this->createNotFoundException();
        }

        return $this->pingLock(
            $page,
            $user,
        );
    }

    #[Route(
        path: '/{organ}/edit/release',
        name: 'edit_release',
        requirements: ['organ' => '\d+'],
        methods: ['POST'],
    )]
    #[IsCsrfTokenValid(
        id: 'body_edit_lock',
        tokenKey: '_csrf_token',
    )]
    public function editRelease(
        Organ $organ,
        #[CurrentUser]
        User $user,
    ): JsonResponse {
        $page = $organ->getOrganInformation();
        if (null === $page) {
            throw $this->createNotFoundException();
        }

        return $this->releaseLock(
            $page,
            $user,
        );
    }

    /**
     * Start a fresh draft off whatever the page says now, which is the only way to change something the board has
     * already decided on.
     */
    #[Route(
        path: '/{organ}/revise',
        name: 'revise',
        requirements: ['organ' => '\d+'],
        methods: ['POST'],
    )]
    #[IsCsrfTokenValid(
        id: new Expression('"body_revise-" ~ args["organ"].getId()'),
        tokenKey: '_csrf_token',
    )]
    public function revise(
        Organ $organ,
        #[CurrentUser]
        User $user,
    ): Response {
        $page = $this->pageToEdit(
            $organ,
            $user,
        );
        $current = $page->getCurrentRevision();

        if (null === $current) {
            return $this->redirectToRoute(
                'admin/decision/bodies/edit',
                ['organ' => $organ->getId()],
            );
        }

        $refusal = $current->getStatus()->reviseRefusal();

        // A draft that is already there is what the body wants to work on, which is not worth a warning.
        if (ReviseRefusal::AlreadyADraft === $refusal) {
            return $this->redirectToRoute(
                'admin/decision/bodies/edit',
                ['organ' => $organ->getId()],
            );
        }

        if (ReviseRefusal::UnderReview === $refusal) {
            $this->addFlash(
                AlertTypes::Warning->value,
                $this->translator->trans(
                    'This page is with the board. Wait for their decision before revising it again.',
                ),
            );

            return $this->redirectToRoute(
                'admin/decision/bodies/view',
                ['organ' => $organ->getId()],
            );
        }

        if (ReviseRefusal::Closed === $refusal) {
            $this->addFlash(
                AlertTypes::Warning->value,
                $this->translator->trans('This page was closed. Get in touch with the board to reopen it.'),
            );

            return $this->redirectToRoute(
                'admin/decision/bodies/view',
                ['organ' => $organ->getId()],
            );
        }

        $draft = $this->reviser->spawnDraft(
            $current,
            $user,
        );
        $this->entityManager->persist($draft);
        $this->entityManager->flush();

        return $this->redirectToRoute(
            'admin/decision/bodies/edit',
            ['organ' => $organ->getId()],
        );
    }

    /**
     * The page to edit, starting one when this body has never had one. Creating it is the same right as editing it: an
     * installed member or the board.
     */
    private function pageToEdit(
        Organ $organ,
        User $user,
    ): OrganInformation {
        $page = $organ->getOrganInformation();

        if (null !== $page) {
            $this->denyAccessUnlessGranted(
                RevisionVoter::SUBMIT,
                $page,
            );

            if (null === $page->getCurrentRevision()) {
                $this->startFirstDraft(
                    $page,
                    $user,
                );
            }

            return $page;
        }

        if (!$this->mayStartAPage($organ)) {
            throw $this->createAccessDeniedException('You are not allowed to write this body\'s page.');
        }

        $page = new OrganInformation();
        $page->setOrgan($organ);
        $organ->setOrganInformation($page);
        $this->entityManager->persist($page);

        $this->startFirstDraft(
            $page,
            $user,
        );

        return $page;
    }

    private function startFirstDraft(
        OrganInformation $page,
        User $user,
    ): void {
        $draft = new OrganInformationRevision();
        $draft->setAuthor($user->getMember());
        $page->addRevision($draft);
        $page->setCurrentRevision($draft);

        $this->entityManager->persist($draft);
        $this->entityManager->flush();
    }

    /**
     * Whether this member may write a page for a body that has none yet. The voter needs a page to read the body off,
     * so before there is one the same question is answered here: the board, or somebody installed in the body.
     */
    private function mayStartAPage(Organ $organ): bool
    {
        if ($this->isGranted(UserRoles::Board->value)) {
            return true;
        }

        $user = $this->getUser();
        if (!$user instanceof User) {
            return false;
        }

        foreach ($user->getMember()->getCurrentOrganInstallations() as $installation) {
            if ($installation->getOrgan()->getId() === $organ->getId()) {
                return true;
            }
        }

        return false;
    }

    /**
     * The bodies whose page this member may write: everything active for the board, and otherwise the ones they are
     * installed in.
     *
     * @return Organ[]
     */
    private function editableOrgans(User $user): array
    {
        if ($this->isGranted(UserRoles::Board->value)) {
            return $this->organRepository->findActive();
        }

        $organs = [];

        foreach ($user->getMember()->getCurrentOrganInstallations() as $installation) {
            $organs[$installation->getOrgan()->getAbbr()] = $installation->getOrgan();
        }

        ksort($organs);

        return array_values($organs);
    }

    /**
     * Store whatever was uploaded, then cut the chosen rectangle out of it. An upload with no crop alongside it is
     * shown whole until somebody picks one, and a crop with no upload moves the rectangle on the image that is already
     * there.
     *
     * Returns whether everything that was asked for actually happened, so the screen can say so when it did not.
     *
     * @param FormInterface<mixed> $form
     */
    private function storeImages(
        FormInterface $form,
        OrganInformationRevision $revision,
    ): bool {
        $uploaded = true;

        $cover = $form->get('bannerFile')->getData();
        if ($cover instanceof UploadedFile) {
            $stored = $this->imageUploadService->uploadSource($cover);

            if (null === $stored) {
                $uploaded = false;
            } else {
                $revision->setBannerSource($stored);
                $revision->setBannerCrop(null);
                $revision->setBannerPath($stored);
            }
        }

        $thumbnail = $form->get('logoFile')->getData();
        if ($thumbnail instanceof UploadedFile) {
            $stored = $this->imageUploadService->uploadSource($thumbnail);

            if (null === $stored) {
                $uploaded = false;
            } else {
                $revision->setLogoSource($stored);
                $revision->setLogoCrop(null);
                $revision->setLogoPath($stored);
            }
        }

        $banner = $this->applyCrop(
            $form->get('bannerCropData')->getData(),
            $revision->getBannerSource(),
            OrganInformationRevisionType::BANNER_MINIMUM_WIDTH,
            $revision->setBannerCrop(...),
            $revision->setBannerPath(...),
        );
        $logo = $this->applyCrop(
            $form->get('logoCropData')->getData(),
            $revision->getLogoSource(),
            OrganInformationRevisionType::LOGO_MINIMUM_WIDTH,
            $revision->setLogoCrop(...),
            $revision->setLogoPath(...),
        );

        return $uploaded
            && $banner
            && $logo;
    }

    /**
     * Whether the image came out of this with the crop it was asked for. Nothing to cut is not a failure; a cut that
     * could not be made is.
     *
     * @param callable(array<string, float>|null):void $rememberCrop
     * @param callable(string|null):void               $rememberPath
     */
    private function applyCrop(
        mixed $rectangle,
        ?string $source,
        int $minimumWidth,
        callable $rememberCrop,
        callable $rememberPath,
    ): bool {
        if (
            !is_array($rectangle)
            || null === $source
        ) {
            return true;
        }

        $cropped = $this->imageUploadService->applyCrop(
            $source,
            $rectangle,
            $minimumWidth,
        );
        if (null === $cropped) {
            return false;
        }

        $rememberCrop($rectangle);
        $rememberPath($cropped);

        return true;
    }
}
