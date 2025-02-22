<?php

declare(strict_types=1);

namespace User\Authentication\Storage;

use DateTime;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Laminas\Authentication\Storage\Session as SessionStorage;
use Laminas\Http\Header\SetCookie;
use Laminas\Http\Request;
use Laminas\Http\Response;
use UnexpectedValueException;

use function bin2hex;
use function file_get_contents;
use function is_readable;
use function openssl_random_pseudo_bytes;
use function strtotime;

class UserSession extends SessionStorage
{
    private const string JWT_COOKIE_NAME = 'GEWISSESSTOKEN';
    private const string JWT_KEY_ALGORITHM = 'RS256';

    private bool $rememberMe;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    public function __construct(
        private readonly Request $request,
        private readonly Response $response,
        private readonly array $config,
    ) {
        parent::__construct('Laminas_Auth_User');
    }

    /**
     * Set whether we should remember this session or not.
     */
    public function setRememberMe(bool $rememberMe): void
    {
        $this->rememberMe = $rememberMe;
    }

    /**
     * Defined by Laminas\Authentication\Storage\StorageInterface.
     */
    public function isEmpty(): bool
    {
        if (!parent::isEmpty()) {
            return false;
        }

        return !$this->validateSession();
    }

    /**
     * Check if there is a session stored in the database and load it when possible.
     */
    protected function validateSession(): bool
    {
        $key = $this->getPublicKey();

        // Check if the key is readable.
        if (false === $key) {
            return false;
        }

        $cookies = $this->request->getHeaders()->get('cookie');
        if (!isset($cookies[self::JWT_COOKIE_NAME])) {
            return false;
        }

        // Stop validation if we are destroying the session.
        if ($this->response->getHeaders()->has('set-cookie')) {
            foreach ($this->response->getHeaderS()->get('set-cookie') as $cookie) {
                if (
                    self::JWT_COOKIE_NAME === $cookie->getName()
                    && 'deleted' === $cookie->getValue()
                ) {
                    return false;
                }
            }
        }

        try {
            $session = JWT::decode($cookies[self::JWT_COOKIE_NAME], new Key($key, self::JWT_KEY_ALGORITHM));
        } catch (UnexpectedValueException) {
            // This is a generic exception thrown by the JWT library. To ensure that if something goes wrong while
            // decrypting the cookie, unset it. This ensures that people with the cookie do not end up in a loop.
            $this->clearCookie();

            return false;
        }

        // Check if the session has not expired.
        $now = (new DateTime())->getTimestamp();
        if ($now >= $session->exp) {
            return false;
        }

        parent::write($session->lidnr);

        $this->saveSession($session->lidnr);

        return true;
    }

    /**
     * Defined by Laminas\Authentication\Storage\StorageInterface.
     *
     * @inheritDoc
     */
    public function write($contents): void
    {
        parent::write($contents);

        if (!$this->rememberMe) {
            return;
        }

        $this->saveSession($contents);
    }

    /**
     * Store the current session.
     */
    protected function saveSession(int $lidnr): void
    {
        $key = $this->getPrivateKey();

        // Check if the key is readable.
        if (false === $key) {
            return;
        }

        $token = [
            'iss' => 'https://gewis.nl/',
            'lidnr' => $lidnr,
            'exp' => (new DateTime('+2 weeks'))->getTimestamp(),
            'iat' => (new DateTime())->getTimestamp(),
            'nonce' => bin2hex(openssl_random_pseudo_bytes(16)),
        ];

        $jwt = JWT::encode($token, $key, self::JWT_KEY_ALGORITHM);

        $this->saveCookie($jwt);
    }

    /**
     * Defined by Laminas\Authentication\Storage\StorageInterface.
     */
    public function clear(): void
    {
        // Clear the session
        $this->setRememberMe(false);

        parent::write(null);

        parent::clear();

        $this->clearCookie();
    }

    /**
     * Store the session token as a cookie.
     */
    protected function saveCookie(string $jwt): void
    {
        $sessionToken = new SetCookie(self::JWT_COOKIE_NAME, $jwt, strtotime('+2 weeks'), '/');
        $sessionToken = $this->setCookieParameters($sessionToken);

        $this->response->getHeaders()->addHeader($sessionToken);
    }

    /**
     * Destroy the cookie holding the stored session.
     */
    protected function clearCookie(): void
    {
        $sessionToken = new SetCookie(self::JWT_COOKIE_NAME, 'deleted', strtotime('-1 Year'), '/');
        $sessionToken = $this->setCookieParameters($sessionToken);

        $this->response->getHeaders()->addHeader($sessionToken);
    }

    /**
     * Set specific cookie parameters.
     */
    private function setCookieParameters(SetCookie $sessionToken): SetCookie
    {
        // Use secure cookies in production
        if ('production' === APP_ENV) {
            $sessionToken->setSecure(true)
                ->setHttponly(true)
                ->setSameSite(SetCookie::SAME_SITE_LAX);
        }

        return $sessionToken;
    }

    /**
     * Get the private key to use for JWT.
     */
    protected function getPrivateKey(): false|string
    {
        if (!is_readable($this->config['jwt_key_path'])) {
            return false;
        }

        return file_get_contents($this->config['jwt_key_path']);
    }

    /**
     * Get the public key to use for JWT.
     */
    protected function getPublicKey(): false|string
    {
        if (!is_readable($this->config['jwt_pub_key_path'])) {
            return false;
        }

        return file_get_contents($this->config['jwt_pub_key_path']);
    }
}
