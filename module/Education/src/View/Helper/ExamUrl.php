<?php

namespace Education\View\Helper;

use Education\Model\Exam;
use Education\Service\Exam as ExamService;
use Laminas\View\Helper\AbstractHelper;

class ExamUrl extends AbstractHelper
{
    /**
     * Exam service.
     *
     * @var ExamService
     */
    protected ExamService $examService;

    /**
     * Education data dir.
     *
     * @var string
     */
    protected string $dir;

    /**
     * Get the exam URL.
     *
     * @param Exam $exam
     *
     * @return string
     */
    public function __invoke(Exam $exam): string
    {
        return $this->getView()->basePath() . '/' . $this->getDir() . '/' . $this->examService->examToFilename($exam);
    }

    /**
     * Get the data dir.
     *
     * @return string
     */
    public function getDir(): string
    {
        return $this->dir;
    }

    /**
     * Set the data dir.
     *
     * @param string $dir
     */
    public function setDir(string $dir): void
    {
        $this->dir = $dir;
    }

    /**
     * Get the authentication service.
     *
     * @return ExamService
     */
    public function getExamService(): ExamService
    {
        return $this->examService;
    }

    /**
     * Set the authentication service.
     *
     * @param ExamService $examService
     */
    public function setExamService(ExamService $examService): void
    {
        $this->examService = $examService;
    }
}
