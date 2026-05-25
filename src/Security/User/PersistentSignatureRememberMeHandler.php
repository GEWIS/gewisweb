<?php

declare(strict_types=1);

namespace App\Security\User;

use App\Entity\User\Session;
use App\Repository\User\SessionRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Override;
use Psr\Log\LoggerInterface;
use RuntimeException;
use SensitiveParameter;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CookieTheftException;
use Symfony\Component\Security\Core\Signature\SignatureHasher;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\RememberMe\AbstractRememberMeHandler;
use Symfony\Component\Security\Http\RememberMe\RememberMeDetails;
use Throwable;

use function base64_encode;
use function count;
use function explode;
use function hash;
use function hash_equals;
use function hash_hmac;
use function implode;
use function random_bytes;
use function rtrim;
use function sprintf;
use function strtr;
use function trim;

/**
 * Combines the best of Symfony's two built-in remember-me handlers:
 *
 * - {@see \Symfony\Component\Security\Http\RememberMe\SignatureRememberMeHandler}: HMAC-signed tokens, invalidates on
 *   property change
 * - {@see \Symfony\Component\Security\Http\RememberMe\PersistentRememberMeHandler}: revocable per-device, cookie-theft
 *   detection, and rich session metadata
 */
class PersistentSignatureRememberMeHandler extends AbstractRememberMeHandler
{
    private const string COOKIE_DELIMITER = ':';
    private const string HASH_ALGO = 'sha256';
    private const string HMAC_ALGO = 'sha256';

    private const array SIGNATURE_PROPERTIES = [
        'password',
        'passwordChangedOn',
        'forceReloginAt',
        'totpSecret',
    ];

    private readonly SignatureHasher $signatureHasher;

    /**
     * @param UserProviderInterface<UserInterface> $userProvider
     */
    public function __construct(
        UserProviderInterface $userProvider,
        RequestStack $requestStack,
        private readonly EntityManagerInterface $entityManager,
        private readonly SessionRepository $repository,
        private readonly UserAgentParser $userAgentParser,
        #[Autowire(param: 'kernel.secret')]
        #[SensitiveParameter]
        private readonly string $secret,
        private readonly string $firewallName,
        private readonly int $tokenLifetime,
        string $cookieName,
        ?LoggerInterface $logger = null,
    ) {
        if ('' === trim($secret)) {
            throw new RuntimeException(sprintf(
                'kernel.secret is empty for firewall "%s". Set APP_SECRET in your .env file.',
                $firewallName,
            ));
        }

        parent::__construct(
            $userProvider,
            $requestStack,
            [
                'name' => $cookieName,
                'lifetime' => $tokenLifetime,
                'secure' => null,
                'samesite' => Cookie::SAMESITE_LAX, // Required to be `lax`, otherwise user is always logged out on cross-origin requests.
            ],
            $logger,
        );

        $this->signatureHasher = new SignatureHasher(
            PropertyAccess::createPropertyAccessor(),
            self::SIGNATURE_PROPERTIES,
            $secret,
        );
    }

    #[Override]
    public function createRememberMeCookie(UserInterface $user): void
    {
        $request = $this->requestStack->getMainRequest();

        if (null === $request) {
            $this->logger?->warning(
                'createRememberMeCookie called without an active request.',
                [
                    'firewall' => $this->firewallName,
                ],
            );

            return;
        }

        [
            $series,
            $rawToken, $expiresAt
        ] = $this->createSession(
            $user,
            $request,
        );

        $this->createCookie(new RememberMeDetails(
            $user->getUserIdentifier(),
            $expiresAt->getTimestamp(),
            $series . self::COOKIE_DELIMITER . $rawToken,
        ));
    }

    /**
     * Verifies a remember-me cookie against the persisted session row, then
     * rotates the token. Invoked by {@see AbstractRememberMeHandler::consumeRememberMeCookie()}
     * after the user has been loaded via the user provider.
     */
    #[Override]
    protected function processRememberMe(
        RememberMeDetails $rememberMeDetails,
        UserInterface $user,
    ): void {
        $parts = explode(
            self::COOKIE_DELIMITER,
            $rememberMeDetails->getValue(),
            2,
        );

        if (2 !== count($parts)) {
            throw new AuthenticationException('Malformed remember-me cookie value.');
        }

        [
            $series, $rawToken
        ] = $parts;
        $session = $this->repository->findOneBySeries($series);

        if (null === $session) {
            throw new AuthenticationException('Remember-me series not found in storage.');
        }

        // Ensure we only process remember-me requests for the correct firewall.
        if ($session->getFirewallName() !== $this->firewallName) {
            $this->logger?->warning(
                'Cross-firewall token replay attempt rejected.',
                [
                    'series' => $series,
                    'token_firewall' => $session->getFirewallName(),
                    'request_firewall' => $this->firewallName,
                ],
            );

            throw new AuthenticationException('Remember-me token does not belong to this firewall.');
        }

        // The remember-me token must not be expired.
        if ($session->isExpired()) {
            $this->entityManager->remove($session);
            $this->entityManager->flush();

            throw new AuthenticationException('Remember-me token has expired.');
        }

        // Ensure integrity of the remember-me token.
        $expectedSig = $this->computeRowSignature(
            $session->getSeries(),
            $session->getHashedToken(),
            $session->getUserIdentifier(),
            $session->getExpiresAt(),
        );

        if (
            !hash_equals(
                $expectedSig,
                $session->getSignature(),
            )
        ) {
            $this->logger?->warning(
                'HMAC mismatch; possible DB tampering or kernel.secret rotation.',
                [
                    'series' => $series,
                    'user' => $session->getUserIdentifier(),
                    'firewall' => $this->firewallName,
                ],
            );
            $this->entityManager->remove($session);
            $this->entityManager->flush();

            throw new AuthenticationException('Remember-me signature is invalid.');
        }

        // Cookie theft detection: a valid series with a stale token means the cookie was consumed twice. The rotation
        // below means whoever presented it first invalidated everyone else's copy; the loser shows up here with a token
        // that no longer hashes to what is stored. We cannot tell attacker from victim, so we burn every session for
        // this user on this firewall.
        if (
            !hash_equals(
                $session->getHashedToken(),
                hash(
                    self::HASH_ALGO,
                    $rawToken,
                ),
            )
        ) {
            $this->logger?->emergency(
                'Cookie theft detected! Invalidating ALL sessions for this user on this firewall.',
                [
                    'series' => $series,
                    'user' => $session->getUserIdentifier(),
                    'firewall' => $this->firewallName,
                ],
            );
            $this->repository->deleteAllForUserOnFirewall(
                $session->getUserIdentifier(),
                $this->firewallName,
            );
            $this->entityManager->flush();

            throw new CookieTheftException(
                'Remember-me token was already consumed. All sessions on this firewall have been invalidated.',
            );
        }

        // If any of the properties in the signature changed, detect that and force log out.
        if (
            !hash_equals(
                $session->getSignaturePropertiesHash(),
                $this->computeUserPropertiesHash($user),
            )
        ) {
            $this->logger?->info(
                'User properties fingerprint changed – session invalidated.',
                [
                    'series' => $series,
                    'user' => $session->getUserIdentifier(),
                    'firewall' => $this->firewallName,
                    'properties' => self::SIGNATURE_PROPERTIES,
                ],
            );
            $this->entityManager->remove($session);
            $this->entityManager->flush();
            $this->createCookie(null);

            throw new AuthenticationException(
                'Remember-me session invalidated: user security properties have changed.',
            );
        }

        // Rotate the token on every successful use. This is what makes the theft check above meaningful: an old raw
        // token reappearing after rotation is the smoking gun. Without rotation, we could not distinguish legitimate
        // reuse from a replayed stolen cookie.
        $newRawToken = $this->generateToken();
        $newHashedToken = hash(
            self::HASH_ALGO,
            $newRawToken,
        );

        $session->setHashedToken($newHashedToken);
        $session->setSignature($this->computeRowSignature(
            $session->getSeries(),
            $newHashedToken,
            $session->getUserIdentifier(),
            $session->getExpiresAt(),
        ));
        $session->setLastUsedAt(new DateTimeImmutable());
        $this->entityManager->flush();

        $this->createCookie(new RememberMeDetails(
            $user->getUserIdentifier(),
            $session->getExpiresAt()->getTimestamp(),
            $series . self::COOKIE_DELIMITER . $newRawToken,
        ));
    }

    #[Override]
    public function clearRememberMeCookie(): void
    {
        $request = $this->requestStack->getMainRequest();

        if (null !== $request) {
            $series = $this->getSeriesFromCookie($request);

            if (null !== $series) {
                $session = $this->repository->findOneBySeries($series);

                if (
                    null !== $session
                    && $session->getFirewallName() === $this->firewallName
                ) {
                    $this->entityManager->remove($session);
                    $this->entityManager->flush();
                }
            }
        }

        parent::clearRememberMeCookie();
    }

    public function getSeriesFromCookie(Request $request): ?string
    {
        $cookieValue = $request->cookies->get($this->options['name']);

        if (
            null === $cookieValue
            || '' === $cookieValue
        ) {
            return null;
        }

        try {
            $details = RememberMeDetails::fromRawCookie($cookieValue);
            $parts = explode(
                self::COOKIE_DELIMITER,
                $details->getValue(),
                2,
            );

            return $parts[0];
        } catch (Throwable) {
            return null;
        }
    }

    public function getFirewallName(): string
    {
        return $this->firewallName;
    }

    /** @return array{0: string, 1: string, 2: DateTimeImmutable} [series, rawToken, expiresAt] */
    private function createSession(
        UserInterface $user,
        Request $request,
    ): array {
        $series = $this->generateToken(44);
        $rawToken = $this->generateToken();
        $expiresAt = new DateTimeImmutable('+' . $this->tokenLifetime . ' seconds');

        $hashedToken = hash(
            self::HASH_ALGO,
            $rawToken,
        );
        $signature = $this->computeRowSignature(
            $series,
            $hashedToken,
            $user->getUserIdentifier(),
            $expiresAt,
        );

        $userAgent = $request->headers->get(
            'User-Agent',
            '',
        );
        $meta = $this->userAgentParser->parse($userAgent);

        $session = new Session();
        $session->setSeries($series);
        $session->setHashedToken($hashedToken);
        $session->setSignature($signature);
        $session->setSignaturePropertiesHash($this->computeUserPropertiesHash($user));
        $session->setFirewallName($this->firewallName);
        $session->setUserIdentifier($user->getUserIdentifier());
        $session->setCreatedAt(new DateTimeImmutable());
        $session->setExpiresAt($expiresAt);
        $session->setLastUsedAt(new DateTimeImmutable());
        $session->setUserAgent($userAgent);
        $session->setIpAddress($request->getClientIp() ?? '');
        $session->setPhpSessionId($request->getSession()->getId());
        $session->setDeviceType($meta['type']);
        $session->setBrowser($meta['browser']);
        $session->setOperatingSystem($meta['operatingSystem']);

        $this->entityManager->persist($session);
        $this->entityManager->flush();

        return [
            $series,
            $rawToken,
            $expiresAt,
        ];
    }

    /**
     * Expiry is tracked separately in the DB row. so we pass 0 to make the fingerprint stable across requests.
     */
    private function computeUserPropertiesHash(UserInterface $user): string
    {
        return $this->signatureHasher->computeSignatureHash(
            $user,
            0,
        );
    }

    private function computeRowSignature(
        string $series,
        string $hashedToken,
        string $userIdentifier,
        DateTimeImmutable $expiresAt,
    ): string {
        $data = implode(
            ':',
            [
                $series,
                $hashedToken,
                $userIdentifier,
                $expiresAt->getTimestamp(),
            ],
        );

        return hash_hmac(
            self::HMAC_ALGO,
            $data,
            $this->secret,
        );
    }

    /**
     * @param positive-int $bytes
     */
    private function generateToken(int $bytes = 32): string
    {
        return rtrim(
            strtr(
                base64_encode(random_bytes($bytes)),
                '+/',
                '-_',
            ),
            '=',
        );
    }
}
