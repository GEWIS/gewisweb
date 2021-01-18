<?php

namespace User\Authentication\Storage;

use Firebase\JWT\JWT;
use Zend\Authentication\Storage,
    User\Model\Session as SessionModel,
    Zend\Http\Header\SetCookie;

class Session extends Storage\Session
{

    /**
     * @var The service manager
     */
    protected $sm;

    /**
     * @var boolean indicating whether we should remember the user
     */
    protected $rememberMe;

    /**
     * Set whether we should remember this session or not.
     *
     * @param int $rememberMe
     */
    public function setRememberMe($rememberMe = 0)
    {
        $this->rememberMe = $rememberMe;
        if ($rememberMe) {
            $this->saveSession($this->session->{$this->member}->getLidnr());
        }
    }

    /**
     * Ensure that this session is no longer remembered.
     */
    public function forgetMe()
    {
        $this->rememberMe = false;
    }

    /**
     * Defined by Zend\Authentication\Storage\StorageInterface
     *
     * @return bool
     */
    public function isEmpty()
    {
        if (isset($this->session->{$this->member})) {
            return false;
        }

        return !$this->validateSession();

    }

    /**
     * Check if there is a session stored in the database and load it when possible.
     *
     * @return bool indicating whether a session was loaded.
     */
    protected function validateSession()
    {
        $key = $this->getPublicKey();
        if (!$key) {
            // Key not readable
            return false;
        }

        $request = $this->sm->get('Request');
        $cookies = $request->getHeaders()->get('cookie');
        if (!isset($cookies->SESSTOKEN)) {
            return false;
        }
        try {
            $session = JWT::decode($cookies->SESSTOKEN, $key, ['RS256']);
        } catch (\UnexpectedValueException $e) {
            return false;
        }

        $this->session->{$this->member} = $session->lidnr;
        $this->saveSession($session->lidnr);
        return true;
    }

    /**
     * Defined by Zend\Authentication\Storage\StorageInterface
     *
     * @return mixed
     */
    public function read()
    {
        return $this->session->{$this->member};
    }

    /**
     * Defined by Zend\Authentication\Storage\StorageInterface
     *
     * @param  mixed $contents
     * @return void
     */
    public function write($contents)
    {
        $this->session->{$this->member} = $contents;
        if ($this->rememberMe) {
            $this->saveSession($contents);
        }
    }

    /**
     * Store the current session.
     *
     * @param $lidnr the lidnr of the logged in user
     *
     * @return SessionModel
     */
    public function saveSession($lidnr)
    {
        $key = $this->getPrivateKey();
        if (!$key) {
            // Key not readable
            return false;
        }
        $token = [
            'iss' => 'https://gewis.nl/',
            'lidnr' => $lidnr,
            'exp' => (new \DateTime('+2 weeks'))->getTimestamp(),
            'iat' => (new \DateTime())->getTimestamp(),
            'nonce' => bin2hex(openssl_random_pseudo_bytes(16))
        ];

        $jwt = JWT::encode($token, $key, 'RS256');

        $this->saveCookie($jwt);
    }

    /**
     * Defined by Zend\Authentication\Storage\StorageInterface
     *
     * @return void
     */
    public function clear()
    {
        // Clear the session
        unset($this->session->{$this->member});
        $this->clearCookie();

    }

    /**
     * Store the session token as a cookie
     * @param string $jwt The session token to store
     */
    protected function saveCookie($jwt)
    {
        $sessionToken = new SetCookie('SESSTOKEN', $jwt, strtotime('+2 weeks'), '/');
        // Use secure cookies in production
        if (APP_ENV === 'production') {
            $sessionToken->setSecure(true)->setHttponly(true);
        }

        $config = $this->sm->get('config');
        $sessionToken->setDomain($config['cookie_domain']);

        $response = $this->sm->get('Response');
        $response->getHeaders()->addHeader($sessionToken);
    }

    protected function clearCookie()
    {
        $sessionToken = new SetCookie('SESSTOKEN', 'deleted', strtotime('-1 Year'), '/');
        $sessionToken->setSecure(true)->setHttponly(true);
        $response = $this->sm->get('Response');
        $response->getHeaders()->addHeader($sessionToken);
    }

    public function __construct($sm)
    {
        $this->sm = $sm;
        parent::__construct(null, null, null);
    }

    /**
     * Get the private key to use for JWT
     * @return string|boolean returns false if the private key is not readable
     */
    protected function getPrivateKey()
    {
        $config = $this->sm->get('config');
        if (!is_readable($config['jwt_key_path'])) {
            return false;
        }
        return file_get_contents($config['jwt_key_path']);
    }

    /**
     * Get the public key to use for JWT
     * @return string|boolean returns false if the public key is not readable
     */
    protected function getPublicKey()
    {
        $config = $this->sm->get('config');
        if (!is_readable($config['jwt_pub_key_path'])) {
            return false;
        }
        return file_get_contents($config['jwt_pub_key_path']);
    }
}
