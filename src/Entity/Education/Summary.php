<?php

declare(strict_types=1);

namespace App\Entity\Education;

use App\Entity\Education\Enums\CourseDocumentTypes;
use App\Repository\Education\SummaryRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Override;

/**
 * Summary.
 */
#[Entity(repositoryClass: SummaryRepository::class)]
class Summary extends CourseDocument
{
    /**
     * Author of the summary.
     */
    #[Column(
        type: Types::STRING,
        nullable: true,
    )]
    private ?string $author = null;

    #[Override]
    public function getType(): CourseDocumentTypes
    {
        return CourseDocumentTypes::Summary;
    }

    /**
     * Get the author.
     */
    public function getAuthor(): ?string
    {
        return $this->author;
    }

    /**
     * Set the author.
     */
    public function setAuthor(?string $author): void
    {
        $this->author = $author;
    }
}
