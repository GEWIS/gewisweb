<?php

declare(strict_types=1);

namespace App\Controller\Education;

use App\Entity\Application\Enums\AlertTypes;
use App\Entity\Education\Course;
use App\Entity\Education\CourseDocument;
use App\Entity\User\Enums\UserRoles;
use App\Form\Education\CourseType;
use App\Message\Education\FlattenCourseDocumentMessage;
use App\Repository\Education\CourseDocumentRepository;
use App\Repository\Education\CourseRepository;
use App\Service\Education\CourseAdminService;
use App\Service\Education\EducationOverviewCountsProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

use function strtoupper;

/**
 * Courses are plain reference data rather than revisable content, so they are edited directly. A course code is the
 * identity documents hang off, so it is set once and then fixed.
 */
#[IsGranted(
    attribute: UserRoles::Board->value,
    message: 'You are not allowed to administer course material.',
)]
#[Route(
    path: '/admin/education',
    name: 'admin/education/',
)]
class AdminController extends AbstractController
{
    /** Course codes are five to nine alphanumerics, e.g. 2IL50 or 2WBB0. */
    private const string COURSE_CODE = '[A-Za-z0-9]{5,9}';

    public function __construct(
        private readonly CourseRepository $courseRepository,
        private readonly CourseDocumentRepository $documentRepository,
        private readonly CourseAdminService $courseAdminService,
        private readonly EducationOverviewCountsProvider $countsProvider,
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
            'education/admin/index.html.twig',
            [
                'counts' => $this->countsProvider->counts(),
                'unprocessed' => $this->documentRepository->findNotReady(),
            ],
        );
    }

    #[Route(
        path: '/courses/add',
        name: 'courses/add',
        methods: [
            'GET',
            'POST',
        ],
    )]
    public function addCourse(Request $request): Response
    {
        $course = new Course();
        $form = $this->createForm(
            CourseType::class,
            $course,
        );
        $form->handleRequest($request);

        if (
            $form->isSubmitted()
            && $form->isValid()
        ) {
            $this->courseAdminService->save($course);

            $this->addFlash(
                AlertTypes::Success->value,
                $this->translator->trans('The course was added.'),
            );

            return $this->redirectToRoute('admin/education/index');
        }

        return $this->render(
            'education/admin/course-form.html.twig',
            [
                'form' => $form,
                'course' => null,
            ],
        );
    }

    #[Route(
        path: '/courses/{code}/edit',
        name: 'courses/edit',
        requirements: ['code' => self::COURSE_CODE],
        methods: [
            'GET',
            'POST',
        ],
    )]
    public function editCourse(
        Request $request,
        string $code,
    ): Response {
        $course = $this->requireCourse($code);

        $form = $this->createForm(
            CourseType::class,
            $course,
            ['edit' => true],
        );
        $form->handleRequest($request);

        if (
            $form->isSubmitted()
            && $form->isValid()
        ) {
            $this->courseAdminService->save($course);

            $this->addFlash(
                AlertTypes::Success->value,
                $this->translator->trans('The course was saved.'),
            );

            return $this->redirectToRoute(
                'admin/education/courses/documents',
                ['code' => $course->getCode()],
            );
        }

        return $this->render(
            'education/admin/course-form.html.twig',
            [
                'form' => $form,
                'course' => $course,
            ],
        );
    }

    #[Route(
        path: '/courses/{code}/delete',
        name: 'courses/delete',
        requirements: ['code' => self::COURSE_CODE],
        methods: ['POST'],
    )]
    #[IsCsrfTokenValid(
        id: new Expression('"education_course_delete-" ~ args["code"]'),
        tokenKey: '_csrf_token',
    )]
    public function deleteCourse(string $code): Response
    {
        $course = $this->requireCourse($code);

        $this->courseAdminService->deleteCourse($course);

        $this->addFlash(
            AlertTypes::Success->value,
            $this->translator->trans('The course and everything filed under it were removed.'),
        );

        return $this->redirectToRoute('admin/education/index');
    }

    #[Route(
        path: '/courses/{code}',
        name: 'courses/documents',
        requirements: ['code' => self::COURSE_CODE],
    )]
    public function courseDocuments(string $code): Response
    {
        $course = $this->requireCourse($code);

        return $this->render(
            'education/admin/course.html.twig',
            ['course' => $course],
        );
    }

    #[Route(
        path: '/documents/{document}/delete',
        name: 'documents/delete',
        requirements: ['document' => '\d+'],
        methods: ['POST'],
    )]
    #[IsCsrfTokenValid(
        id: new Expression('"education_document_delete-" ~ args["document"].getId()'),
        tokenKey: '_csrf_token',
    )]
    public function deleteDocument(CourseDocument $document): Response
    {
        $code = $document->getCourse()->getCode();

        $this->courseAdminService->deleteDocument($document);

        $this->addFlash(
            AlertTypes::Success->value,
            $this->translator->trans('The document was removed.'),
        );

        return $this->redirectToRoute(
            'admin/education/courses/documents',
            ['code' => $code],
        );
    }

    /**
     * Rendering fails on an upload poppler cannot read; replacing the binary or the file is the fix, and this is how
     * the result is picked up without editing anything.
     */
    #[Route(
        path: '/documents/{document}/reprocess',
        name: 'documents/reprocess',
        requirements: ['document' => '\d+'],
        methods: ['POST'],
    )]
    #[IsCsrfTokenValid(
        id: new Expression('"education_document_reprocess-" ~ args["document"].getId()'),
        tokenKey: '_csrf_token',
    )]
    public function reprocessDocument(CourseDocument $document): Response
    {
        $this->messageBus->dispatch(new FlattenCourseDocumentMessage($document->getId() ?? 0));

        $this->addFlash(
            AlertTypes::Success->value,
            $this->translator->trans('The document was queued for processing.'),
        );

        return $this->redirectToRoute(
            'admin/education/courses/documents',
            ['code' => $document->getCourse()->getCode()],
        );
    }

    private function requireCourse(string $code): Course
    {
        $course = $this->courseRepository->findWithDocuments(strtoupper($code));
        if (null === $course) {
            throw $this->createNotFoundException();
        }

        return $course;
    }
}
