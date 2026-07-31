<?php

declare(strict_types=1);

namespace App\Tests\Integration\Twig\Extensions;

use App\Twig\Extensions\RealtimeExtension;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorToken;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\InMemoryUser;

/**
 * Which Mercure topics a page may subscribe to decides what the hub is allowed to push at whoever is holding the
 * browser, so this runs over the real firewall map and the real voters rather than a stand-in for them.
 */
final class RealtimeExtensionTest extends KernelTestCase
{
    public function testAPasserByOnlyGetsTheBroadcastTopic(): void
    {
        self::assertSame(
            ['gewis/public'],
            $this->topicsFor(null),
        );
    }

    public function testSomeoneSignedInAlsoGetsTheirOwnTopic(): void
    {
        self::assertSame(
            [
                'gewis/public',
                'gewis/user/main/8025',
            ],
            $this->topicsFor(new UsernamePasswordToken(
                $this->member(),
                'main',
                ['ROLE_USER'],
            )),
        );
    }

    /**
     * A sign-in still waiting on its second factor has the member on the token but has not signed in yet. Handing it
     * their topic would push their notifications to whoever holds the password.
     */
    public function testASignInWaitingOnItsSecondFactorGetsNoTopicOfItsOwn(): void
    {
        self::assertSame(
            ['gewis/public'],
            $this->topicsFor(new TwoFactorToken(
                new UsernamePasswordToken(
                    $this->member(),
                    'main',
                    ['ROLE_USER'],
                ),
                null,
                'main',
                ['totp'],
            )),
        );
    }

    /**
     * @return string[]
     */
    private function topicsFor(?TokenInterface $token): array
    {
        self::bootKernel();

        self::getContainer()->get('request_stack')->push(Request::create('/en/'));
        self::getContainer()->get('security.token_storage')->setToken($token);

        return self::getContainer()->get(RealtimeExtension::class)->realtimeTopics();
    }

    private function member(): InMemoryUser
    {
        return new InMemoryUser(
            '8025',
            null,
        );
    }
}
