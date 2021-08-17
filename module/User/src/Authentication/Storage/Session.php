<?php

namespace User\Authentication\Storage;

use DateTime;
use Firebase\JWT\JWT;
use Laminas\Authentication\Storage\Session as SessionStorage;
use Laminas\Http\{
    Header\SetCookie,
    Request,
    Response,
};
use UnexpectedValueException;

class Session extends SessionStorage
{
    /**
     * @var bool indicating whether we should remember the user
     */
    protected bool $rememberMe;

    /**
     * @var Request
     */
    private Request $request;

    /**
     * @var Response
     */
    private Response $response;

    /**
     * @var array
     */
    private array $config;

    public function __construct(
        Request $request,
        Response $response,
        array $config,
    ) {
        $this->request = $request;
        $this->response = $response;
        $this->config = $config;

        parent::__construct();
    }

    /**
     * Set whether we should remember this session or not.
     *
     * @param bool $rememberMe
     */
    public function setRememberMe(bool $rememberMe): void
    {
        $this->rememberMe = $rememberMe;
    }

    /**
     * Defined by Laminas\Authentication\Storage\StorageInterface.
     *
     * @return bool
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
     *
     * @return bool indicating whether a session was loaded
     */
    protected function validateSession(): bool
    {
        $key = $this->getPublicKey();

        // Check if the key is readable.
        if (!$key) {
            return false;
        }

        $cookies = $this->request->getHeaders()->get('cookie');
        if (!isset($cookies->GEWISSESSTOKEN)) {
            return false;
        }

        // Stop validation if we are destroying the session.
        if ($this->response->getHeaders()->has('set-cookie')) {
            foreach ($this->response->getHeaderS()->get('set-cookie') as $cookie) {
                if ($cookie->getName() === 'GEWISSESSTOKEN' && $cookie->getValue() === 'deleted') {
                    return false;
                }
            }
        }

        try {
            $session = JWT::decode($cookies->GEWISSESSTOKEN, $key, ['RS256']);
        } catch (UnexpectedValueException $e) {
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
     * @param mixed $contents
     *
     * @return void
     */
    public function write($contents): void
    {
        parent::write($contents);

        if ($this->rememberMe) {
            $this->saveSession($contents);
        }
    }

    /**
     * Store the current session.
     *
     * @param int $lidnr the lidnr of the logged in user
     *
     * @return void
     */
    protected function saveSession(int $lidnr): void
    {
        $key = $this->getPrivateKey();

        // Check if the key is readable.
        if (!$key) {
            return;
        }

        $token = [
            'iss' => 'https://gewis.nl/',
            'lidnr' => $lidnr,
            'exp' => (new DateTime('+2 weeks'))->getTimestamp(),
            'iat' => (new DateTime())->getTimestamp(),
            'nonce' => bin2hex(openssl_random_pseudo_bytes(16)),
        ];

        $jwt = JWT::encode($token, $key, 'RS256');

        $this->saveCookie($jwt);
    }

    /**
     * Defined by Laminas\Authentication\Storage\StorageInterface.
     *
     * @return void
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
     *
     * @param string $jwt The session token to store
     *
     * @return void
     */
    protected function saveCookie(string $jwt): void
    {
        $sessionToken = new SetCookie('GEWISSESSTOKEN', $jwt, strtotime('+2 weeks'), '/');
        $sessionToken = $this->setCookieParameters($sessionToken);

        $this->response->getHeaders()->addHeader($sessionToken);
    }

    /**
     * Destroy the cookie holding the stored session.
     *
     * @return void
     */
    protected function clearCookie(): void
    {
        $sessionToken = new SetCookie('GEWISSESSTOKEN', 'deleted', strtotime('-1 Year'), '/');
        $sessionToken = $this->setCookieParameters($sessionToken);

        $this->response->getHeaders()->addHeader($sessionToken);
    }

    /**
     * @param SetCookie $sessionToken
     *
     * @return SetCookie
     */
    private function setCookieParameters(SetCookie $sessionToken): SetCookie
    {
        // Use secure cookies in production
        if (APP_ENV === 'production') {
            $sessionToken->setSecure(true)->setHttponly(true);
        }

        $sessionToken->setDomain($this->config['cookie_domain']);

        return $sessionToken;
    }

    /**
     * Get the private key to use for JWT.
     *
     * @return string|bool returns false if the private key is not readable
     */
    protected function getPrivateKey(): bool|string
    {
        if (!is_readable($this->config['jwt_key_path'])) {
            return false;
        }

        return file_get_contents($this->config['jwt_key_path']);
    }

    /**
     * Get the public key to use for JWT.
     *
     * @return string|bool returns false if the public key is not readable
     */
    protected function getPublicKey(): bool|string
    {
        if (!is_readable($this->config['jwt_pub_key_path'])) {
            return false;
        }

        return file_get_contents($this->config['jwt_pub_key_path']);
    }
}
