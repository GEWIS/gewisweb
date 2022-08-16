<?php

namespace User\Authentication\Service;

use DateInterval;
use DateTime;
use Doctrine\ORM\EntityManager;
use User\Mapper\{
    LoginAttempt as LoginAttemptMapper,
    User as UserMapper,
};
use User\Model\{
    LoginAttempt as LoginAttemptModel,
    User as UserModel,
};

class LoginAttempt
{
    public function __construct(
        private readonly string $remoteAddress,
        private readonly LoginAttemptMapper $loginAttemptMapper,
        private readonly UserMapper $userMapper,
        private readonly array $rateLimitConfig,
    ) {
    }

    /**
     * @param UserModel $user
     */
    public function logFailedLogin(UserModel $user): void
    {
        $attempt = new LoginAttemptModel();

        $attempt->setIp($this->remoteAddress);
        $attempt->setTime(new DateTime());

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
        $this->userMapper->getEntityManager()->clear();

        return $this->userMapper->findByLidnr($user->getLidnr());
    }

    /**
     * @param UserModel $user
     *
     * @return bool
     */
    public function loginAttemptsExceeded(UserModel $user): bool
    {
        $ip = $this->remoteAddress;
        $since = (new DateTime())->sub(new DateInterval('PT' . $this->rateLimitConfig['lockout_time'] . 'M'));

        if ($this->loginAttemptMapper->getFailedAttemptCount($since, $ip) > $this->rateLimitConfig['ip']) {
            return true;
        }

        if ($this->loginAttemptMapper->getFailedAttemptCount($since, $ip, $user) > $this->rateLimitConfig['user']) {
            return true;
        }

        return false;
    }
}
