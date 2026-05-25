<?php

declare(strict_types=1);

namespace App\Entity\Education;

use App\Repository\Education\SummaryRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;

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
