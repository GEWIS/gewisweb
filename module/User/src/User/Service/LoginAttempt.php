<?php

namespace User\Service;

use DateInterval;
use DateTime;
use Doctrine\ORM\EntityManager;

class LoginAttempt
{

    /**
     * @var string
     */
    private $remoteAddress;

    /**
     * @var array
     */
    private $rateLimitConfig;

    /**
     * @var \User\Mapper\LoginAttempt
     */
    private $loginAttemptMapper;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var \User\Mapper\User
     */
    private $userMapper;

    public function __construct(
        string $remoteAddress,
        EntityManager $entityManager,
        \User\Mapper\LoginAttempt $loginAttemptMapper,
        \User\Mapper\User $userMapper,
        array $rateLimitConfig
    )
    {
        $this->remoteAddress = $remoteAddress;
        $this->loginAttemptMapper = $loginAttemptMapper;
        $this->rateLimitConfig = $rateLimitConfig;
        $this->entityManager = $entityManager;
        $this->userMapper = $userMapper;
    }

    public function logFailedLogin($user, $type)
    {
        $attempt = new \User\Model\LoginAttempt();
        $attempt->setIp($this->remoteAddress);
        $attempt->setTime(new DateTime());
        $attempt->setType($type);
        $user = $this->detachUser($user);
        $attempt->setUser($user);
        $this->loginAttemptMapper->persist($attempt);
    }

    public function detachUser($user)
    {
        /*
         * TODO: This probably shouldn't be neccessary
         * Yes, this is some sort of horrible hack to make the entity manager happy again. If anyone wants to waste
         * their day figuring out what kind of dark magic is upsetting the entity manager here, be my guest.
         * This hack only is needed when we want to flush the entity manager during login.
         */
        $this->entityManager->clear();

        return $this->userMapper->findByLidnr($user->getLidnr());
    }

    public function loginAttemptsExceeded($type, $user)
    {
        $ip = $this->remoteAddress;
        $since = (new DateTime())->sub(new DateInterval('PT' . $this->rateLimitConfig[$type]['lockout_time'] . 'M'));
        if ($this->loginAttemptMapper->getFailedAttemptCount($since, $type, $ip) > $this->rateLimitConfig[$type]['ip']) {
            return true;
        }
        if ($this->loginAttemptMapper->getFailedAttemptCount($since, $type, $ip, $user) > $this->rateLimitConfig[$type]['user']) {
            return true;
        }

        return false;
    }
}
