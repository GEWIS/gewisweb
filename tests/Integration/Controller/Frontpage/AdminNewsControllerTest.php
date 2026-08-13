<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Frontpage;

use App\Controller\Frontpage\AdminNewsController;
use App\Entity\Frontpage\Enums\NewsCategory;
use App\Entity\Frontpage\NewsItem;
use App\Entity\User\User;
use App\Repository\Frontpage\NewsItemRepository;
use App\Tests\Integration\DatabaseTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

use function count;
use function strval;

/**
 * A news item is written, changed and taken down straight from the admin, with nothing reviewing it in between. These
 * take one all the way round and pin that removing it is a POST rather than something a crawler can trip over.
 *
 * The actions are invoked directly with the current user on the token storage, as the other admin tests do and for
 * the same reason: the session guard force-logs-out any session with no managed-session row behind it.
 */
final class AdminNewsControllerTest extends DatabaseTestCase
{
    public function testANewsItemIsWrittenChangedAndTakenDown(): void
    {
        $before = count($this->repository()->findAll());

        $this->controller()->create($this->form([
            'date' => '2026-08-01',
            'category' => NewsCategory::Committees->value,
            'pinned' => '1',
            'dutchTitle' => 'Iets nieuws',
            'englishTitle' => 'Something new',
            'dutchContent' => 'Er is iets gebeurd.',
            'englishContent' => 'Something happened.',
        ]));

        $items = $this->repository()->findAll();
        self::assertCount(
            $before + 1,
            $items,
        );

        $written = $this->repository()->findOneBy(['englishTitle' => 'Something new']);
        self::assertInstanceOf(
            NewsItem::class,
            $written,
        );
        self::assertSame(
            NewsCategory::Committees,
            $written->getCategory(),
        );
        self::assertTrue($written->getPinned());

        $this->controller()->edit(
            $this->form([
                'date' => $written->getDate()->format('Y-m-d'),
                'category' => NewsCategory::Education->value,
                'dutchTitle' => $written->getDutchTitle(),
                'englishTitle' => 'Something changed',
                'dutchContent' => $written->getDutchContent(),
                'englishContent' => $written->getEnglishContent(),
            ]),
            $written,
        );

        self::assertSame(
            'Something changed',
            $written->getEnglishTitle(),
        );
        self::assertSame(
            NewsCategory::Education,
            $written->getCategory(),
        );
        // The box was left unticked this time, which has to un-pin it rather than leave it as it was.
        self::assertFalse($written->getPinned());

        $this->controller()->delete($written);

        self::assertCount(
            $before,
            $this->repository()->findAll(),
        );
    }

    /**
     * An item with no title is not written, and the form comes back saying so rather than a half-filled row landing
     * in the archive.
     */
    public function testAnItemWithoutATitleComesBackWithTheForm(): void
    {
        $before = count($this->repository()->findAll());

        $response = $this->controller()->create($this->form([
            'date' => '2026-08-01',
            'category' => NewsCategory::Board->value,
            'dutchTitle' => '',
            'englishTitle' => '',
            'dutchContent' => 'Er is iets gebeurd.',
            'englishContent' => 'Something happened.',
        ]));

        self::assertCount(
            $before,
            $this->repository()->findAll(),
        );
        self::assertStringContainsString(
            'Title',
            strval($response->getContent()),
        );
    }

    private function controller(): AdminNewsController
    {
        return self::getContainer()->get(AdminNewsController::class);
    }

    private function repository(): NewsItemRepository
    {
        return self::getContainer()->get(NewsItemRepository::class);
    }

    /**
     * The form as the admin page posts it. CSRF is stateless here, so a same-origin request naming the double-submit
     * cookie is what the manager accepts.
     *
     * @param array<string, string> $fields
     */
    private function form(array $fields): Request
    {
        $request = new Request(
            request: ['news_item' => $fields + ['_csrf_token' => 'csrf-token']],
            server: ['HTTP_SEC_FETCH_SITE' => 'same-origin'],
        );
        $request->setMethod(Request::METHOD_POST);

        $this->authenticateAsBoard($request);

        return $request;
    }

    private function authenticateAsBoard(Request $request): void
    {
        $user = $this->entityManager->getRepository(User::class)->find(8000);
        self::assertInstanceOf(
            User::class,
            $user,
        );

        self::getContainer()->get('security.token_storage')->setToken(new UsernamePasswordToken(
            $user,
            'main',
            ['ROLE_BOARD'],
        ));

        $session = self::getContainer()->get('session.factory')->createSession();
        self::assertInstanceOf(
            FlashBagAwareSessionInterface::class,
            $session,
        );
        $request->setSession($session);
        self::getContainer()->get('request_stack')->push($request);
    }
}
