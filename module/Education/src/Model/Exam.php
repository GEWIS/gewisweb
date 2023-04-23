<?php

declare(strict_types=1);

namespace Education\Model;

use Doctrine\ORM\Mapping\{
    Column,
    Entity,
};
use Education\Model\Enums\ExamTypes;

/**
 * Exam.
 */
#[Entity]
class Exam extends CourseDocument
{
    /**
     * Type of exam.
     */
    #[Column(
        type: "string",
        enumType: ExamTypes::class,
    )]
    protected ExamTypes $examType;

    /**
     * Get the type.
     */
    public function getExamType(): ExamTypes
    {
        return $this->examType;
    }

    /**
     * Set the type.
     */
    public function setExamType(ExamTypes $examType): void
    {
        $this->examType = $examType;
    }
}
