<?php

declare(strict_types=1);

namespace App\Controller\Frontpage;

use App\Entity\Application\Enums\AlertTypes;
use App\Entity\Application\Enums\ImageProfile;
use App\Entity\Application\Enums\ImageVariant;
use App\Entity\Application\Enums\Languages;
use App\Entity\Application\Enums\StorageNamespace;
use App\Entity\Frontpage\FrontpageLocalisedText;
use App\Entity\Frontpage\Page;
use App\Entity\User\Enums\UserRoles;
use App\Form\Frontpage\PageType;
use App\Message\Photo\ProcessImageVariantsMessage;
use App\Repository\Frontpage\PageRepository;
use App\Service\Application\FileStorage;
use App\Service\Application\FileStorageException;
use App\Service\Application\ImageUrlBuilder;
use App\Service\Frontpage\PageAdminService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

use function strval;
use function usort;

/**
 * The pages the association writes itself: everything that is neither news nor a body's own page.
 *
 * A page is addressed by its own words rather than by an id, so the overview is grouped the way the address reads.
 * Saving one goes through {@see PageAdminService}, which is where the content is sanitized.
 */
#[Route(
    path: '/admin/pages',
    name: 'admin/frontpage/pages/',
)]
#[IsGranted(UserRoles::Board->value)]
class AdminPageController extends AbstractController
{
    public function __construct(
        private readonly PageRepository $pageRepository,
        private readonly PageAdminService $pageAdminService,
        private readonly FileStorage $fileStorage,
        private readonly ImageUrlBuilder $imageUrlBuilder,
        private readonly MessageBusInterface $messageBus,
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
            'frontpage/admin/pages/index.html.twig',
            ['pages' => $this->pagesByAddress()],
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
    public function create(Request $request): Response
    {
        $page = new Page();
        $page->setCategory(new FrontpageLocalisedText());
        $page->setSubCategory(new FrontpageLocalisedText());
        $page->setName(new FrontpageLocalisedText());
        $page->setTitle(new FrontpageLocalisedText());
        $page->setContent(new FrontpageLocalisedText());
        $page->setRequiredRole(UserRoles::Guest);

        $form = $this->createForm(
            PageType::class,
            $page,
        )->handleRequest($request);

        if (
            !$form->isSubmitted()
            || !$form->isValid()
        ) {
            return $this->render(
                'frontpage/admin/pages/create.html.twig',
                ['form' => $form],
            );
        }

        $this->pageAdminService->save($page);

        $this->addFlash(
            AlertTypes::Success->value,
            $this->translator->trans('The page was created.'),
        );

        return $this->redirectToRoute('admin/frontpage/pages/index');
    }

    #[Route(
        path: '/{page}/edit',
        name: 'edit',
        requirements: ['page' => '\d+'],
        methods: [
            'GET',
            'POST',
        ],
    )]
    public function edit(
        Request $request,
        Page $page,
    ): Response {
        $form = $this->createForm(
            PageType::class,
            $page,
        )->handleRequest($request);

        if (
            !$form->isSubmitted()
            || !$form->isValid()
        ) {
            return $this->render(
                'frontpage/admin/pages/edit.html.twig',
                [
                    'form' => $form,
                    'customPage' => $page,
                ],
            );
        }

        $this->pageAdminService->save($page);

        $this->addFlash(
            AlertTypes::Success->value,
            $this->translator->trans('The page was saved.'),
        );

        return $this->redirectToRoute('admin/frontpage/pages/index');
    }

    #[Route(
        path: '/{page}/delete',
        name: 'delete',
        requirements: ['page' => '\d+'],
        methods: ['POST'],
    )]
    #[IsCsrfTokenValid(
        id: new Expression('"page_delete-" ~ args["page"].getId()'),
        tokenKey: '_csrf_token',
    )]
    public function delete(Page $page): Response
    {
        $this->pageAdminService->delete($page);

        $this->addFlash(
            AlertTypes::Success->value,
            $this->translator->trans('The page was removed.'),
        );

        return $this->redirectToRoute('admin/frontpage/pages/index');
    }

    #[Route(
        path: '/upload',
        name: 'upload',
        methods: ['POST'],
    )]
    #[IsCsrfTokenValid(
        id: 'page_image_upload',
        tokenKey: '_csrf_token',
    )]
    public function upload(Request $request): JsonResponse
    {
        $file = $request->files->get('image');

        if (!$file instanceof UploadedFile) {
            return new JsonResponse(
                ['error' => $this->translator->trans('No image was uploaded.')],
                Response::HTTP_BAD_REQUEST,
            );
        }

        try {
            $stored = $this->fileStorage->store(
                StorageNamespace::PageImage,
                $file->getPathname(),
            );
        } catch (FileStorageException) {
            return new JsonResponse(
                ['error' => $this->translator->trans('That file cannot be used as an image on a page.')],
                Response::HTTP_BAD_REQUEST,
            );
        }

        // The sizes a page asks for are made now rather than by whoever opens the page first, which would otherwise be
        // the editor a moment from here.
        $this->messageBus->dispatch(new ProcessImageVariantsMessage(
            $stored->path,
            ImageProfile::PageImage,
        ));

        return new JsonResponse([
            // The widest a page column ever is, which is what an image dropped into one should be served at.
            'url' => $this->imageUrlBuilder->url(
                $stored->path,
                ImageVariant::W1280,
            ),
        ]);
    }

    /**
     * Every page in the order its address reads, so the overview groups a category with what sits under it rather
     * than listing rows in whatever order they were written.
     *
     * @return list<Page>
     */
    private function pagesByAddress(): array
    {
        $pages = $this->pageRepository->findAll();
        $language = Languages::current();

        usort(
            $pages,
            static function (
                Page $a,
                Page $b,
            ) use ($language): int {
                return [
                    strval($a->getCategory()->getText($language)),
                    strval($a->getSubCategory()->getText($language)),
                    strval($a->getName()->getText($language)),
                ] <=> [
                    strval($b->getCategory()->getText($language)),
                    strval($b->getSubCategory()->getText($language)),
                    strval($b->getName()->getText($language)),
                ];
            },
        );

        return $pages;
    }
}
