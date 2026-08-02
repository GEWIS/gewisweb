<?php

declare(strict_types=1);

namespace App\Controller\Decision;

use App\Entity\Decision\ReferenceDocument;
use App\Entity\User\Enums\UserRoles;
use App\Entity\User\User;
use App\Security\User\SudoVoter;
use App\Service\Application\FileStorageException;
use App\Service\Decision\ReferenceDocumentService;
use App\Service\Decision\VersionLabelSuggester;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

use function assert;
use function mb_strlen;
use function trim;

/**
 * Board management of the association-wide reference document library. The page itself is the
 * {@see \App\Twig\Components\Decision\Admin\ReferenceLibrary} live component; the upload endpoints here follow the
 * same XHR convention as the meeting document uploads.
 */
#[IsGranted(UserRoles::Board->value)]
#[IsGranted(SudoVoter::ATTRIBUTE)]
#[Route(
    path: '/admin/meetings/reference',
    name: 'admin/meetings/reference/',
)]
class AdminReferenceDocumentController extends AbstractController
{
    public function __construct(
        private readonly ReferenceDocumentService $referenceDocumentService,
        private readonly VersionLabelSuggester $versionLabelSuggester,
        private readonly TranslatorInterface $translator,
    ) {
    }

    #[Route(
        path: '',
        name: 'index',
    )]
    public function index(): Response
    {
        return $this->render('decision/admin/reference/index.html.twig');
    }

    #[Route(
        path: '/upload',
        name: 'upload',
        methods: ['POST'],
    )]
    public function upload(Request $request): JsonResponse
    {
        $file = $request->files->get('file');
        if (!$file instanceof UploadedFile) {
            return $this->uploadError($this->translator->trans('No file was uploaded.'));
        }

        $name = trim($request->request->getString('name'));
        if (
            mb_strlen($name) < 2
            || mb_strlen($name) > 128
        ) {
            return $this->uploadError(
                $this->translator->trans('The document name must be between 2 and 128 characters long.'),
            );
        }

        try {
            $this->referenceDocumentService->createDocument(
                $name,
                $file,
                $this->versionLabel($request),
                $this->actor(),
            );
        } catch (FileStorageException $exception) {
            return $this->uploadError($exception->getMessage());
        }

        return new JsonResponse(['ok' => true]);
    }

    #[Route(
        path: '/{document}/versions',
        name: 'version_upload',
        requirements: ['document' => '\d+'],
        methods: ['POST'],
    )]
    public function uploadVersion(
        Request $request,
        ReferenceDocument $document,
    ): JsonResponse {
        $file = $request->files->get('file');
        if (!$file instanceof UploadedFile) {
            return $this->uploadError($this->translator->trans('No file was uploaded.'));
        }

        try {
            $this->referenceDocumentService->uploadVersion(
                $document,
                $file,
                $this->versionLabel($request),
                $this->actor(),
            );
        } catch (FileStorageException $exception) {
            return $this->uploadError($exception->getMessage());
        }

        return new JsonResponse(['ok' => true]);
    }

    private function versionLabel(Request $request): string
    {
        $label = trim($request->request->getString('versionLabel'));

        return '' === $label
            ? $this->versionLabelSuggester->suggest(null)
            : $label;
    }

    private function actor(): User
    {
        $user = $this->getUser();
        assert($user instanceof User);

        return $user;
    }

    private function uploadError(string $message): JsonResponse
    {
        return new JsonResponse(
            ['error' => $message],
            Response::HTTP_UNPROCESSABLE_ENTITY,
        );
    }
}
