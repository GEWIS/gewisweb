<?php

declare(strict_types=1);

namespace App\Service\Frontpage;

use App\Entity\Frontpage\Page;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Writing and removing a custom page.
 *
 * Every save goes through the sanitizer here rather than in the form, so a page written through the visual editor, a
 * page pasted into a textarea and a page put together by anything else all reach the database having been through the
 * same check. What is stored is the sanitized content and nothing else.
 */
final readonly class PageAdminService
{
    public function __construct(
        private PageContentSanitizer $sanitizer,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function save(Page $page): void
    {
        $content = $page->getContent();
        $content->updateValues(
            $this->sanitizer->sanitize($content->getValueEN()),
            $this->sanitizer->sanitize($content->getValueNL()),
        );

        $this->entityManager->persist($page);
        $this->entityManager->flush();
    }

    public function delete(Page $page): void
    {
        $this->entityManager->remove($page);
        $this->entityManager->flush();
    }
}
