<?php

namespace Education\Model;

use Doctrine\ORM\Mapping\{
    Column,
    Entity,
};

/**
 * Summary.
 */
#[Entity]
class Summary extends Exam
{
    /**
     * Author of the summary.
     */
    #[Column(type: "string")]
    protected $author;

    /**
     * Get the author.
     *
     * @return string
     */
    public function getAuthor(): string
    {
        return $this->author;
    }

    /**
     * Set the author.
     *
     * @param string $author
     */
    public function setAuthor(string $author): void
    {
        $this->author = $author;
    }
}
