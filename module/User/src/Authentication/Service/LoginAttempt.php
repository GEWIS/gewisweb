<?php

declare(strict_types=1);

namespace User\Authentication\Service;

use Application\Model\IdentityInterface;
use DateInterval;
use DateTime;
use User\Mapper\LoginAttempt as LoginAttemptMapper;
use User\Model\CompanyUser as CompanyUserModel;
use User\Model\LoginAttempt as LoginAttemptModel;
use User\Model\User as UserModel;

class LoginAttempt
{
    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    public function __construct(
        private readonly string $remoteAddress,
        private readonly LoginAttemptMapper $loginAttemptMapper,
        private readonly array $rateLimitConfig,
    ) {
    }

    /**
     * Log a failed login attempt.
     */
    public function logFailedLogin(IdentityInterface $user): void
    {
        $attempt = new LoginAttemptModel();

        $attempt->setIp($this->remoteAddress);
        $attempt->setTime(new DateTime());

        if ($user instanceof CompanyUserModel) {
            $attempt->setCompanyUser($user);
        } elseif ($user instanceof UserModel) {
            $attempt->setUser($user);
        }

        $this->loginAttemptMapper->persist($attempt);
    }

    /**
     * Check if there are too many login tries for a specific account.
     */
    public function loginAttemptsExceeded(IdentityInterface $user): bool
    {
        $ip = $this->remoteAddress;
        $since = (new DateTime())->sub(new DateInterval('PT' . $this->rateLimitConfig['lockout_time'] . 'M'));

        if ($this->loginAttemptMapper->getFailedAttemptCount($since, $ip) > $this->rateLimitConfig['ip']) {
            return true;
        }

        $maxLoginAttempts = $this->rateLimitConfig['user'];
        if ($user instanceof CompanyUserModel) {
            $maxLoginAttempts = $this->rateLimitConfig['company'];
        }

        return $this->loginAttemptMapper->getFailedAttemptCount($since, $ip, $user) > $maxLoginAttempts;
    }

    /**
     * Delete all (failed) login attempts that are older than 3 months.
     *
     * We can automatically DELETE all login attempts at once instead of retrieving them and iterating over them.
     */
    public function deletedOldLoginAttempts(): void
    {
        $this->loginAttemptMapper->deleteLoginAttemptsOtherThan3Months();
    }
}
