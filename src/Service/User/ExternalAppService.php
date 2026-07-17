<?php

declare(strict_types=1);

namespace App\Service\User;

use App\Entity\User\ExternalApp;
use App\Entity\User\ExternalAppAuthentication;
use App\Entity\User\User;
use DateTime;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Jose\Component\Core\AlgorithmManager;
use Jose\Component\KeyManagement\JWKFactory;
use Jose\Component\Signature\Algorithm\HS512;
use Jose\Component\Signature\JWSBuilder;
use Jose\Component\Signature\Serializer\CompactSerializer;

use function bin2hex;
use function json_encode;
use function random_bytes;

use const JSON_THROW_ON_ERROR;

class ExternalAppService
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    /**
     * Mint a short-lived signed token for the external application and return the callback URL the browser should
     * navigate to. Also records the authentication, which drives the reminder and the member's security overview.
     */
    public function callbackWithToken(
        ExternalApp $app,
        User $user,
    ): string {
        $member = $user->getMember();
        $issuedAt = new DateTimeImmutable();

        $claims = [
            'iss' => 'https://gewis.nl/',
            'exp' => $issuedAt->modify('+5 minutes')->getTimestamp(),
            'iat' => $issuedAt->getTimestamp(),
            'nonce' => bin2hex(random_bytes(16)),
        ];

        foreach ($app->getClaims() as $claim) {
            $claims[$claim->value] = $claim->getValue($member);
        }

        $this->logAuthentication(
            $app,
            $user,
        );

        return $app->getCallback() . '?token=' . $this->sign(
            $app,
            $claims,
        );
    }

    /**
     * @param array<string, bool|int|string|null> $claims
     */
    private function sign(
        ExternalApp $app,
        array $claims,
    ): string {
        $key = JWKFactory::createFromSecret($app->getSecret());
        $builder = new JWSBuilder(new AlgorithmManager([new HS512()]));
        $jws = $builder->create()
            ->withPayload(json_encode($claims, JSON_THROW_ON_ERROR))
            ->addSignature(
                $key,
                ['alg' => 'HS512'],
            )
            ->build();

        return new CompactSerializer()->serialize(
            $jws,
            0,
        );
    }

    private function logAuthentication(
        ExternalApp $app,
        User $user,
    ): void {
        $authentication = new ExternalAppAuthentication();
        $authentication->setUser($user);
        $authentication->setExternalApp($app);
        $authentication->setTime(new DateTime());

        $this->entityManager->persist($authentication);
        $this->entityManager->flush();
    }
}
