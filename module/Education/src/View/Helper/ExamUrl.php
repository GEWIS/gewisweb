<?php

declare(strict_types=1);

namespace Education\View\Helper;

use Education\Model\Exam;
use Education\Service\Exam as ExamService;
use Laminas\View\Helper\AbstractHelper;

class ExamUrl extends AbstractHelper
{
    /**
     * Exam service.
     */
    protected ExamService $examService;

    /**
     * Education data dir.
     */
    protected string $dir;

    /**
     * Get the exam URL.
     */
    public function __invoke(Exam $exam): string
    {
        return $this->getView()->basePath($this->getDir() . '/' . $this->examService->courseDocumentToFilename($exam));
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
        return $this->examService;
    }

    /**
     * Set the authentication service.
     */
    public function setExamService(ExamService $examService): void
    {
        $this->examService = $examService;
    }
}
