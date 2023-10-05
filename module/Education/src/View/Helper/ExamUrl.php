<?php

declare(strict_types=1);

namespace Education\View\Helper;

use Education\Model\Exam;
use Education\Service\Course as CourseService;
use Laminas\View\Helper\AbstractHelper;

class ExamUrl extends AbstractHelper
{
    /**
     * Course service.
     */
    protected CourseService $courseService;

    /**
     * Education data dir.
     */
    protected string $dir;

    /**
     * Get the exam URL.
     */
    public function __invoke(Exam $exam): string
    {
        return $this->getView()->basePath($this->getDir() . '/' . $this->courseService->courseDocumentToFilename($exam));
    }

    /**
     * Get the data dir.
     */
    public function getDir(): string
    {
        return $this->dir;
    }

    /**
     * Set the data dir.
     */
    public function setDir(string $dir): void
    {
        $this->dir = $dir;
    }

    /**
     * Get the authentication service.
     */
    public function getExamService(): ExamService
    {
        return $this->courseService;
    }

    /**
     * Set the authentication service.
     */
    public function setCourseService(CourseService $courseService): void
    {
        $this->courseService = $courseService;
    }
}
