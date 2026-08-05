<?php

declare(strict_types=1);

namespace App\Controller\Education;

use App\Entity\Application\Enums\AlertTypes;
use App\Entity\Education\CourseDocumentStaging;
use App\Entity\User\Enums\UserRoles;
use App\Entity\User\User;
use App\Form\Education\StagedDocumentType;
use App\Repository\Education\CourseDocumentStagingRepository;
use App\Service\Application\FileStorageException;
use App\Service\Education\DocumentStagingService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

use function strtoupper;
use function trim;

/**
 * The department sends dozens of exams at a time, named to no standard, so the flow is in two halves: the files go up
 * first and land in staging with everything guessed from their names, and one form per upload corrects the guesses and
 * publishes it. Nothing an upload contains is visible to a member until then.
 */
#[IsGranted(
    attribute: UserRoles::Board->value,
    message: 'You are not allowed to administer course material.',
)]
#[Route(
    path: '/admin/education/documents',
    name: 'admin/education/documents/',
)]
class AdminUploadController extends AbstractController
{
    public function __construct(
        private readonly CourseDocumentStagingRepository $stagingRepository,
        private readonly DocumentStagingService $stagingService,
        private readonly TranslatorInterface $translator,
    ) {
    }

    #[Route(
        path: '/upload',
        name: 'upload',
        methods: ['GET'],
    )]
    public function upload(): Response
    {
        $staged = $this->stagingRepository->findPending();

        $forms = [];
        foreach ($staged as $document) {
            $forms[$document->getId() ?? 0] = $this->createForm(
                StagedDocumentType::class,
                $document,
            )->createView();
        }

        return $this->render(
            'education/admin/upload.html.twig',
            [
                'staged' => $staged,
                'forms' => $forms,
            ],
        );
    }

    /**
     * Called once per file by the upload page, so a rejected file reports itself without taking the batch with it.
     */
    #[Route(
        path: '/stage',
        name: 'stage',
        methods: ['POST'],
    )]
    #[IsCsrfTokenValid(
        id: 'education-stage-document',
        tokenKey: '_csrf_token',
    )]
    public function stage(
        Request $request,
        #[CurrentUser]
        ?User $user,
    ): JsonResponse {
        $file = $request->files->get('file');
        if (!$file instanceof UploadedFile) {
            return $this->json(
                ['error' => $this->translator->trans('No file was uploaded.')],
                Response::HTTP_BAD_REQUEST,
            );
        }

        try {
            $staged = $this->stagingService->stage(
                $file,
                $user,
            );
        } catch (FileStorageException $e) {
            return $this->json(
                ['error' => $e->getMessage()],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        return $this->json([
            'id' => $staged->getId(),
            'filename' => $staged->getOriginalFilename(),
        ]);
    }

    #[Route(
        path: '/{staged}/publish',
        name: 'publish',
        requirements: ['staged' => '\d+'],
        methods: ['POST'],
    )]
    public function publish(
        Request $request,
        CourseDocumentStaging $staged,
    ): Response {
        $form = $this->createForm(
            StagedDocumentType::class,
            $staged,
        );
        $form->handleRequest($request);

        if (
            !$form->isSubmitted()
            || !$form->isValid()
        ) {
            $this->addFlash(
                AlertTypes::Danger->value,
                $this->translator->trans('That upload could not be published; check what it says.'),
            );

            return $this->redirectToRoute('admin/education/documents/upload');
        }

        $staged->setCourseCode(strtoupper(trim($staged->getCourseCode() ?? '')));

        try {
            $this->stagingService->publish($staged);
        } catch (Throwable $e) {
            $this->addFlash(
                AlertTypes::Danger->value,
                $e->getMessage(),
            );

            return $this->redirectToRoute('admin/education/documents/upload');
        }

        $this->addFlash(
            AlertTypes::Success->value,
            $this->translator->trans('The document was published and queued for processing.'),
        );

        return $this->redirectToRoute('admin/education/documents/upload');
    }

    #[Route(
        path: '/{staged}/discard',
        name: 'discard',
        requirements: ['staged' => '\d+'],
        methods: ['POST'],
    )]
    #[IsCsrfTokenValid(
        id: new Expression('"education_staged_discard-" ~ args["staged"].getId()'),
        tokenKey: '_csrf_token',
    )]
    public function discard(CourseDocumentStaging $staged): Response
    {
        $this->stagingService->discard($staged);

        $this->addFlash(
            AlertTypes::Success->value,
            $this->translator->trans('The upload was discarded.'),
        );

        return $this->redirectToRoute('admin/education/documents/upload');
    }
}
