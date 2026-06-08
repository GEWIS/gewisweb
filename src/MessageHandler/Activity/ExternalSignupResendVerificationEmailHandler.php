<?php

declare(strict_types=1);

namespace App\MessageHandler\Activity;

use App\Message\Activity\ExternalSignupResendVerificationEmail;
use App\Repository\Activity\SignupListRepository;
use App\Service\Activity\SignupManager;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Re-issues and re-sends an external sign-up's confirmation e-mail. The sign-up existence lookup lives in
 * {@see SignupManager::resendVerification()} and runs here, in the worker, so the dispatching request stays
 * constant-time regardless of whether the address is signed up (no timing-based enumeration). Silently does nothing if
 * the list is gone, the address is not signed up, or the sign-up is already confirmed.
 */
#[AsMessageHandler]
class ExternalSignupResendVerificationEmailHandler
{
    public function __construct(
        private readonly SignupListRepository $signupListRepository,
        private readonly SignupManager $signupManager,
    ) {
    }

    public function __invoke(ExternalSignupResendVerificationEmail $message): void
    {
        $signupList = $this->signupListRepository->find($message->getSignupListId());
        if (null === $signupList) {
            return;
        }

        $this->signupManager->resendVerification(
            $signupList,
            $message->getEmail(),
        );
    }
}
