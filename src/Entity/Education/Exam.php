<?php

declare(strict_types=1);

namespace App\Entity\Education;

use App\Entity\Education\Enums\ExamTypes;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;

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
        type: Types::STRING,
        enumType: ExamTypes::class,
    )]
    private ExamTypes $examType;

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
