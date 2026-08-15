<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Frontpage;

use App\Controller\Frontpage\NewsController;
use App\Entity\Frontpage\Enums\NewsCategory;
use App\Entity\Frontpage\NewsItem;
use App\Repository\Frontpage\NewsItemRepository;
use App\Tests\Integration\DatabaseTestCase;
use Symfony\Component\HttpFoundation\Request;

use function strval;

/**
 * The public news pages, rendered as a reader gets them. News is the association speaking rather than a person, so
 * these also pin that the archive and the item page name nobody.
 */
final class NewsControllerTest extends DatabaseTestCase
{
    public function testTheArchiveListsWhatWasPublished(): void
    {
        $this->request();

        $content = strval($this->controller()->index()->getContent());

        foreach (
            [
                NewsCategory::Board,
                NewsCategory::Career,
                NewsCategory::Education,
            ] as $category
        ) {
            self::assertStringContainsString(
                'category=' . $category->value,
                $content,
            );
        }
    }

    public function testTheArchiveIsGroupedIntoMonths(): void
    {
        $this->request();

        self::assertStringContainsString(
            'month-divider',
            strval($this->controller()->index()->getContent()),
        );
    }

    public function testAskingForOneCategoryLeavesTheRestOut(): void
    {
        $this->request();

        $content = strval($this->controller()->index(category: 'education')->getContent());

        $education = $this->anItemIn(NewsCategory::Education);
        $career = $this->anItemIn(NewsCategory::Career);

        self::assertStringContainsString(
            $education->getEnglishTitle(),
            $content,
        );
        self::assertStringNotContainsString(
            $career->getEnglishTitle(),
            $content,
        );
    }

    /**
     * A category that no longer exists reads as no filter at all rather than an error page, which is what a stale
     * bookmark deserves.
     */
    public function testAnUnknownCategoryShowsEverything(): void
    {
        $this->request();

        $content = strval($this->controller()->index(category: 'gossip')->getContent());

        self::assertStringContainsString(
            $this->anItemIn(NewsCategory::Career)->getEnglishTitle(),
            $content,
        );
    }

    /**
     * The same, for a year nothing was written in: the switcher only offers years that have news, so a year outside
     * them can only come from a hand-written address.
     */
    public function testAnEmptyYearShowsEverything(): void
    {
        $this->request();

        $content = strval($this->controller()->index(year: 1899)->getContent());

        self::assertStringContainsString(
            $this->anItemIn(NewsCategory::Career)->getEnglishTitle(),
            $content,
        );
    }

    public function testAnItemPageShowsItsText(): void
    {
        $item = $this->anItemIn(NewsCategory::Board);
        $this->request();

        $content = strval($this->controller()->view($item)->getContent());

        self::assertStringContainsString(
            $item->getEnglishTitle(),
            $content,
        );
    }

    /**
     * The entity has no author and the pages must never grow one, since news is the association speaking. A member's
     * name turning up in the markup is the failure this guards against.
     */
    public function testNothingOnTheNewsPagesNamesAMember(): void
    {
        $item = $this->anItemIn(NewsCategory::Board);
        $this->request();

        $archive = strval($this->controller()->index()->getContent());
        $page = strval($this->controller()->view($item)->getContent());

        foreach (
            [
                $archive,
                $page,
            ] as $content
        ) {
            self::assertStringNotContainsString(
                'ORGAN_ORDINARY',
                $content,
            );
            self::assertStringNotContainsString(
                'author',
                $content,
            );
        }
    }

    private function controller(): NewsController
    {
        return self::getContainer()->get(NewsController::class);
    }

    /**
     * @param array<string, string> $query
     */
    private function request(array $query = []): Request
    {
        $request = Request::create(
            '/en/news',
            'GET',
            $query,
        );
        self::getContainer()->get('request_stack')->push($request);

        return $request;
    }

    private function anItemIn(NewsCategory $category): NewsItem
    {
        $item = self::getContainer()->get(NewsItemRepository::class)->findOneBy(['category' => $category->value]);
        self::assertInstanceOf(
            NewsItem::class,
            $item,
            'The seed is expected to contain news in every category.',
        );

        return $item;
    }
}
