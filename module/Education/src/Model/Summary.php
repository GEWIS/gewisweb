<?php

namespace Education\Model;

use Doctrine\ORM\Mapping as ORM;

/**
 * Summary.
 *
 * @ORM\Entity
 */
class Summary extends Exam
{
    /**
     * Author of the summary.
     *
     * @ORM\Column(type="string")
     */
    protected $author;

    /**
     * Get the author.
     *
     * @return string
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * Set the author.
     *
     * @param string $author
     */
    public function setAuthor($author)
    {
        $this->author = $author;
    }
}
