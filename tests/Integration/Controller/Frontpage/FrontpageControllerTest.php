<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Frontpage;

use App\Controller\Frontpage\FrontpageController;
use App\Entity\User\Enums\UserRoles;
use App\Entity\User\User;
use App\Service\Frontpage\HomePageService;
use App\Tests\Integration\DatabaseTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

use function strval;

/**
 * The home page gathers the news feed, the agenda, the photo-of-the-week, the birthday and the poll blocks through the
 * home-page service. It is public, so it must render for an anonymous visitor (the member-only blocks simply stay
 * hidden) as well as for a logged-in member. Invoked directly, since the codebase has no WebTestCase.
 */
final class FrontpageControllerTest extends DatabaseTestCase
{
    public function testIndexRendersForAnonymousVisitor(): void
    {
        $this->pushRequest();

        $response = $this->controller()->index();

        self::assertSame(
            Response::HTTP_OK,
            $response->getStatusCode(),
        );
    }

    public function testIndexRendersForAMember(): void
    {
        $this->authenticateMember();
        $this->pushRequest();

        $response = $this->controller()->index();

        self::assertSame(
            Response::HTTP_OK,
            $response->getStatusCode(),
        );
    }

    public function testThePageLeadsWithTheNewsAndCarriesThePoll(): void
    {
        $data = self::getContainer()->get(HomePageService::class)->getHomePageData();

        self::assertNotEmpty($data['newsFeed']);
        self::assertNotNull($data['currentPoll']);
        self::assertNotEmpty($data['activities']);
    }

    /**
     * News is the association speaking rather than a person, so no member's name may reach the front page through
     * the feed. The birthday panel names members deliberately and is member-only, so this reads the anonymous page.
     */
    public function testTheNewsOnTheFrontPageNamesNobody(): void
    {
        $this->pushRequest();

        $content = strval($this->controller()->index()->getContent());

        self::assertStringContainsString(
            'Pinned by the board',
            $content,
        );
        self::assertStringNotContainsString(
            'ORGAN_ORDINARY',
            $content,
        );
    }

    private function controller(): FrontpageController
    {
        return self::getContainer()->get(FrontpageController::class);
    }

    private function pushRequest(): void
    {
        $session = self::getContainer()->get('session.factory')->createSession();
        self::assertInstanceOf(
            FlashBagAwareSessionInterface::class,
            $session,
        );

        $request = new Request();
        $request->setSession($session);
        self::getContainer()->get('request_stack')->push($request);
    }

    private function authenticateMember(): void
    {
        $user = $this->entityManager->getRepository(User::class)->find(8030);
        self::assertInstanceOf(
            User::class,
            $user,
            'The seed is expected to contain a user for the member.',
        );

        self::getContainer()->get('security.token_storage')->setToken(
            new UsernamePasswordToken(
                $user,
                'main',
                [UserRoles::Member->value],
            ),
        );
    }
}
