<?php

namespace User\Authentication\Service;

use DateInterval;
use DateTime;
use User\Mapper\{
    CompanyUser as CompanyUserMapper,
    LoginAttempt as LoginAttemptMapper,
    User as UserMapper,
};
use User\Model\{
    CompanyUser as CompanyUserModel,
    LoginAttempt as LoginAttemptModel,
    User as UserModel,
};

class LoginAttempt
{
    public function __construct(
        private readonly string $remoteAddress,
        private readonly LoginAttemptMapper $loginAttemptMapper,
        private readonly CompanyUserMapper $companyUserMapper,
        private readonly UserMapper $userMapper,
        private readonly array $rateLimitConfig,
    ) {
    }

    /**
     * Log a failed login attempt.
     */
    public function logFailedLogin(UserModel|CompanyUserModel $user): void
    {
        $attempt = new LoginAttemptModel();

        $attempt->setIp($this->remoteAddress);
        $attempt->setTime(new DateTime());

        $user = $this->detachUser($user);

        if ($user instanceof CompanyUserModel) {
            $attempt->setCompanyUser($user);
        } else {
            $attempt->setUser($user);
        }

        $this->loginAttemptMapper->persist($attempt);
    }

    public function detachUser(CompanyUserModel|UserModel $user): CompanyUserModel|UserModel|null
    {
        /*
         * TODO: This probably shouldn't be neccessary
         * Yes, this is some sort of horrible hack to make the entity manager happy again. If anyone wants to waste
         * their day figuring out what kind of dark magic is upsetting the entity manager here, be my guest.
         * This hack only is needed when we want to flush the entity manager during login.
         */
        $this->userMapper->getEntityManager()->clear();

        if ($user instanceof CompanyUserModel) {
            return $this->companyUserMapper->find($user->getId());
        }

        if ($user instanceof UserModel) {
            return $this->userMapper->find($user->getId());
        }

        return null;
    }

    /**
     * Check if there are too many login tries for a specific account.
     */
    public function loginAttemptsExceeded(CompanyUserModel|UserModel $user): bool {
        $ip = $this->remoteAddress;
        $since = (new DateTime())->sub(new DateInterval('PT' . $this->rateLimitConfig['lockout_time'] . 'M'));

        if ($this->loginAttemptMapper->getFailedAttemptCount($since, $ip) > $this->rateLimitConfig['ip']) {
            return true;
        }

        $maxLoginAttempts = $this->rateLimitConfig['user'];
        if ($user instanceof CompanyUserModel) {
            $maxLoginAttempts = $this->rateLimitConfig['company'];
        }

        if ($this->loginAttemptMapper->getFailedAttemptCount($since, $ip, $user) > $maxLoginAttempts) {
            return true;
        }

        return false;
    }
}
