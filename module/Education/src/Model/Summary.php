<?php

declare(strict_types=1);

namespace Education\Model;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;

/**
 * Summary.
 */
#[Entity]
class Summary extends CourseDocument
{
    /**
     * Author of the summary.
     */
    #[Column(
        type: 'string',
        nullable: true,
    )]
    protected ?string $author = null;

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
