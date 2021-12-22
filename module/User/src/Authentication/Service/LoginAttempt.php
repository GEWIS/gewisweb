<?php

namespace User\Authentication\Service;

use DateInterval;
use DateTime;
use Doctrine\ORM\EntityManager;
use User\Mapper\{
    LoginAttempt as LoginAttemptMapper,
    User as UserMapper,
};
use User\Model\User as UserModel;

class LoginAttempt
{
    /**
     * @var string
     */
    private string $remoteAddress;

    /**
     * @var array
     */
    private array $rateLimitConfig;

    /**
     * @var LoginAttemptMapper
     */
    private LoginAttemptMapper $loginAttemptMapper;

    /**
     * @var EntityManager
     */
    private EntityManager $entityManager;

    /**
     * @var UserMapper
     */
    private UserMapper $userMapper;

    /**
     * @param string $remoteAddress
     * @param EntityManager $entityManager
     * @param LoginAttemptMapper $loginAttemptMapper
     * @param UserMapper $userMapper
     * @param array $rateLimitConfig
     */
    public function __construct(
        string $remoteAddress,
        EntityManager $entityManager,
        LoginAttemptMapper $loginAttemptMapper,
        UserMapper $userMapper,
        array $rateLimitConfig,
    ) {
        $this->remoteAddress = $remoteAddress;
        $this->loginAttemptMapper = $loginAttemptMapper;
        $this->rateLimitConfig = $rateLimitConfig;
        $this->entityManager = $entityManager;
        $this->userMapper = $userMapper;
    }

    /**
     * @param UserModel $user
     * @param string $type
     */
    public function logFailedLogin(
        UserModel $user,
        string $type,
    ): void {
        $attempt = new \User\Model\LoginAttempt();
        $attempt->setIp($this->remoteAddress);
        $attempt->setTime(new DateTime());
        $attempt->setType($type);
        $user = $this->detachUser($user);
        $attempt->setUser($user);
        $this->loginAttemptMapper->persist($attempt);
    }

    /**
     * @param UserModel $user
     *
     * @return UserModel|null
     */
    public function detachUser(UserModel $user): ?UserModel
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

    /**
     * @param UserModel $user
     * @param string $type
     *
     * @return bool
     */
    public function loginAttemptsExceeded(
        UserModel $user,
        string $type,
    ): bool {
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
