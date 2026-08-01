<?php

declare(strict_types=1);

namespace App\Tests\Integration\Service\Decision;

use App\Entity\Decision\Meeting;
use App\Entity\Decision\Member;
use App\Message\Decision\AuthorizationCreatedEmail;
use App\Message\Decision\AuthorizationRevokedEmail;
use App\Service\Decision\AuthorizationService;
use App\Tests\Integration\DatabaseTestCase;
use Override;
use RuntimeException;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;

use function array_map;
use function array_values;
use function count;

/**
 * The proxy-authorization rules against the seed: member 8005 already receives one valid authorization for the
 * upcoming GMM (and one revoked, which must not count towards the cap of two).
 */
final class AuthorizationServiceTest extends DatabaseTestCase
{
    private AuthorizationService $service;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->service = self::getContainer()->get(AuthorizationService::class);
    }

    public function testAuthorizeCreatesOncePerMemberAndMailsBothSides(): void
    {
        $meeting = $this->upcomingGMM();

        $authorizer = $this->member(8020);
        $authorization = $this->service->authorize(
            $authorizer,
            8006,
            $meeting,
        );

        self::assertSame(
            8006,
            $authorization->getRecipient()->getLidnr(),
        );
        self::assertContains(
            AuthorizationCreatedEmail::class,
            $this->dispatchedMessageClasses(),
        );

        // A second call does not create another authorization.
        self::assertSame(
            $authorization->getId(),
            $this->service->authorize(
                $authorizer,
                8007,
                $meeting,
            )->getId(),
        );
    }

    public function testSelfAuthorizationIsRejected(): void
    {
        $meeting = $this->upcomingGMM();

        $this->expectException(RuntimeException::class);
        $this->service->authorize(
            $this->member(8020),
            8020,
            $meeting,
        );
    }

    public function testARecipientRepresentsAtMostTwoOthers(): void
    {
        $meeting = $this->upcomingGMM();

        // 8005 already receives one valid authorization from the fixtures; a second is fine, a third is not.
        $this->service->authorize(
            $this->member(8020),
            8005,
            $meeting,
        );

        $this->expectException(RuntimeException::class);
        $this->service->authorize(
            $this->member(8021),
            8005,
            $meeting,
        );
    }

    public function testRevokeSetsTheTimestampAndMailsTheRecipient(): void
    {
        $meeting = $this->upcomingGMM();

        $authorizer = $this->member(8010);
        $authorization = $this->service->getCurrentAuthorization(
            $authorizer,
            $meeting,
        );
        self::assertNotNull($authorization);

        $this->service->revoke(
            $authorization,
            $authorizer,
        );

        self::assertNotNull($authorization->getRevokedAt());
        self::assertContains(
            AuthorizationRevokedEmail::class,
            $this->dispatchedMessageClasses(),
        );

        // Revoking someone else's authorization is a no-op.
        $other = $this->service->authorize(
            $this->member(8022),
            8007,
            $meeting,
        );
        $this->service->revoke(
            $other,
            $authorizer,
        );
        self::assertNull($other->getRevokedAt());
    }

    /**
     * The second-soonest upcoming GMM, which the seeded authorizations target.
     */
    private function upcomingGMM(): Meeting
    {
        $meetings = $this->service->getUpcomingALVs();
        self::assertGreaterThanOrEqual(
            2,
            count($meetings),
        );

        return $meetings[1];
    }

    private function member(int $lidnr): Member
    {
        $member = $this->entityManager->find(
            Member::class,
            $lidnr,
        );
        self::assertNotNull($member);

        return $member;
    }

    /**
     * @return list<class-string>
     */
    private function dispatchedMessageClasses(): array
    {
        $transport = self::getContainer()->get('messenger.transport.normal_priority');
        self::assertInstanceOf(
            InMemoryTransport::class,
            $transport,
        );

        return array_values(array_map(
            static fn ($envelope): string => $envelope->getMessage()::class,
            $transport->getSent(),
        ));
    }
}
