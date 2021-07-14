<?php

namespace User\Authentication\Storage;

use DateTime;
use Firebase\JWT\JWT;
use Laminas\Authentication\Storage;
use Laminas\Http\Header\SetCookie;
use UnexpectedValueException;

class Session extends Storage\Session
{
    /**
     * @var bool indicating whether we should remember the user
     */
    protected $rememberMe;
    private $request;
    private $response;
    /**
     * @var array
     */
    private $config;

    public function __construct($request, $response, array $config)
    {
        $this->request = $request;
        $this->response = $response;
        $this->config = $config;
        parent::__construct();
    }

    /**
     * Set whether we should remember this session or not.
     *
     * @param int $rememberMe
     */
    public function setRememberMe($rememberMe = 0)
    {
        $this->rememberMe = $rememberMe;
        if ($rememberMe) {
            $this->saveSession($this->read()->getLidnr());
        }
    }

    /**
     * Defined by Laminas\Authentication\Storage\StorageInterface.
     *
     * @return bool
     */
    public function isEmpty()
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
    protected function validateSession()
    {
        $key = $this->getPublicKey();
        if (!$key) {
            // Key not readable
            return false;
        }

        $cookies = $this->request->getHeaders()->get('cookie');
        if (!isset($cookies->SESSTOKEN)) {
            return false;
        }
        try {
            $session = JWT::decode($cookies->SESSTOKEN, $key, ['RS256']);
        } catch (UnexpectedValueException $e) {
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
    public function write($contents)
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
     */
    protected function saveSession($lidnr)
    {
        $key = $this->getPrivateKey();
        if (!$key) {
            // Key not readable
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
    public function clear()
    {
        // Clear the session
        parent::clear();
        $this->clearCookie();
    }

    /**
     * Store the session token as a cookie.
     *
     * @param string $jwt The session token to store
     */
    protected function saveCookie($jwt)
    {
        $sessionToken = new SetCookie('GEWISSESSTOKEN', $jwt, strtotime('+2 weeks'), '/');
        // Use secure cookies in production
        if (APPLICATION_ENV === 'production') {
            $sessionToken->setSecure(true)->setHttponly(true);
        }

        $sessionToken->setDomain($this->config['cookie_domain']);

        $this->response->getHeaders()->addHeader($sessionToken);
    }

    protected function clearCookie()
    {
        $sessionToken = new SetCookie('GEWISSESSTOKEN', 'deleted', strtotime('-1 Year'), '/');
        $sessionToken->setSecure(true)->setHttponly(true);
        $this->response->getHeaders()->addHeader($sessionToken);
    }

    /**
     * Get the private key to use for JWT.
     *
     * @return string|bool returns false if the private key is not readable
     */
    protected function getPrivateKey()
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
    protected function getPublicKey()
    {
        if (!is_readable($this->config['jwt_pub_key_path'])) {
            return false;
        }

        return file_get_contents($this->config['jwt_pub_key_path']);
    }
}
