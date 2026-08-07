<?php

declare(strict_types=1);

namespace App\Entity\Education;

use App\Entity\Application\Traits\IdentifiableTrait;
use App\Repository\Education\CourseDocumentPageRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\UniqueConstraint;

/**
 * A download is rebuilt from these rather than from the uploaded PDF, so no text from the original ends up in the
 * delivered file. The pixel dimensions are kept alongside the path because the rebuilt PDF sizes each of its pages from
 * them, and reading them back off disk for every download would mean decoding every page twice.
 */
#[Entity(repositoryClass: CourseDocumentPageRepository::class)]
#[UniqueConstraint(columns: ['document_id', 'pageNumber'])]
class CourseDocumentPage
{
    use IdentifiableTrait;

    #[ManyToOne(
        targetEntity: CourseDocument::class,
        inversedBy: 'pages',
    )]
    #[JoinColumn(
        name: 'document_id',
        referencedColumnName: 'id',
        nullable: false,
    )]
    private CourseDocument $document;

    #[Column(type: Types::INTEGER)]
    private int $pageNumber;

    #[Column(type: Types::STRING)]
    private string $path;

    #[Column(type: Types::INTEGER)]
    private int $width;

    #[Column(type: Types::INTEGER)]
    private int $height;

    public function getDocument(): CourseDocument
    {
        return $this->document;
    }

    public function setDocument(CourseDocument $document): void
    {
        $this->document = $document;
    }

    public function getPageNumber(): int
    {
        return $this->pageNumber;
    }

    public function setPageNumber(int $pageNumber): void
    {
        $this->pageNumber = $pageNumber;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function setPath(string $path): void
    {
        $this->path = $path;
    }

    public function getWidth(): int
    {
        return $this->width;
    }

    public function setWidth(int $width): void
    {
        $this->width = $width;
    }

    public function getHeight(): int
    {
        return $this->height;
    }

    public function setHeight(int $height): void
    {
        $this->height = $height;
    }

    /** Decides the orientation of the rebuilt PDF page. */
    public function isPortrait(): bool
    {
        return $this->height >= $this->width;
    }
}
