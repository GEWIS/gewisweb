<?php

declare(strict_types=1);

namespace App\Twig\Extensions;

use App\Entity\User\User;
use App\Security\User\MfaPolicy;
use Override;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Exposes `mfa_enrolment_required()` to templates: true when the current user is in scope per {@see MfaPolicy} but
 * has not yet enrolled. Lets layouts surface the enrolment nag even though `is_granted('ROLE_ADMIN')` returns false
 * for these users (the role is stripped until they enrol).
 */
class MfaEnrolmentExtension extends AbstractExtension
{
    public function __construct(
        private readonly TokenStorageInterface $tokenStorage,
        private readonly MfaPolicy $mfaPolicy,
    ) {
    }

    /**
     * @return TwigFunction[]
     */
    #[Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'mfa_enrolment_required',
                $this->mfaEnrolmentRequired(...),
            ),
        ];
    }

    public function mfaEnrolmentRequired(): bool
    {
        $user = $this->tokenStorage->getToken()?->getUser();
        if (!$user instanceof User) {
            return false;
        }

        return $this->mfaPolicy->isRequiredFor($user)
            && !$this->mfaPolicy->hasEnrolled($user);
    }
}
