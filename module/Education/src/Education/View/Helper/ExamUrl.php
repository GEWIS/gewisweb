<?php

namespace Education\View\Helper;

use Laminas\View\Helper\AbstractHelper;

use Education\Model\Exam;
use Education\Service\Exam as ExamService;

class ExamUrl extends AbstractHelper
{
    /**
     * Exam service..
     *
     * @var ExamService
     */
    protected $examService;

    /**
     * Education data dir.
     *
     * @var string
     */
    protected $dir;

    /**
     * Get the exam URL.
     *
     * @param Exam $exam
     *
     * @return string
     */
    public function __invoke(Exam $exam)
    {
        return $this->getView()->basePath() . '/' . $this->getDir() . '/' . $this->examService->examToFilename($exam);
    }

    /**
     * Get the data dir.
     *
     * @return string
     */
    public function getDir()
    {
        return $this->dir;
    }

    /**
     * Set the data dir.
     *
     * @param string $dir
     */
    public function setDir($dir)
    {
        $this->dir = $dir;
    }

    /**
     * Get the authentication service.
     *
     * @return ExamService
     */
    public function getExamService()
    {
        return $this->examService;
    }

    /**
     * Set the authentication service.
     *
     * @param ExamService $examService
     */
    public function setExamService(ExamService $examService)
    {
        $this->examService = $examService;
    }
}
