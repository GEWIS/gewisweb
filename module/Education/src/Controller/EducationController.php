<?php

declare(strict_types=1);

namespace Education\Controller;

use Education\Form\SearchCourse as SearchCourseForm;
use Education\Service\Course as CourseService;
use Laminas\Http\Response;
use Laminas\Http\Response\Stream;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Override;

class EducationController extends AbstractActionController
{
    public function __construct(
        private readonly CourseService $courseService,
        private readonly SearchCourseForm $searchCourseForm,
    ) {
    }

    #[Override]
    public function indexAction(): ViewModel
    {
        /** @var array $query */
        $query = $this->params()->fromQuery();
        $form = $this->searchCourseForm;

        if (isset($query['query'])) {
            $form->setData($query);

            if ($form->isValid()) {
                $courses = $this->courseService->searchCourse($form->getData());

                return new ViewModel(
                    [
                        'form' => $form,
                        'courses' => $courses,
                    ],
                );
            }
        }

        return new ViewModel(
            [
                'form' => $form,
            ],
        );
    }

    public function courseAction(): Response|ViewModel
    {
        $code = $this->params()->fromRoute('code');
        $course = $this->courseService->getCourse($code);

        // If the course does not exist, trigger 404
        if (null === $course) {
            return $this->notFoundAction();
        }

        return new ViewModel(
            [
                'course' => $course,
            ],
        );
    }

    /**
     * Download an exam.
     */
    public function downloadAction(): Stream|ViewModel
    {
        $id = (int) $this->params()->fromRoute('id');

        $download = $this->courseService->getDocumentDownload($id);

        if (null === $download) {
            return $this->notFoundAction();
        }

        return $download;
    }
}
