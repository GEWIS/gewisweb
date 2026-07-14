<?php

declare(strict_types=1);

namespace App\Security\Application;

use App\Entity\Application\Enums\StorageNamespace;
use App\Entity\User\Enums\UserRoles;
use Override;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * The catch-all serving access checker (lowest priority): public namespaces are freely servable, and any private
 * namespace requires at least an authenticated session. Namespace-specific checkers (e.g. the photos checker added
 * with the domain services, which distinguishes members from graduates) register at a higher priority and take over
 * their namespace.
 */
#[AutoconfigureTag(
    'app.serving_access_checker',
    ['priority' => -100],
)]
final readonly class DefaultServingAccessChecker implements ServingAccessCheckerInterface
{
    public function __construct(
        private Security $security,
    ) {
    }

    #[Override]
    public function supports(StorageNamespace $namespace): bool
    {
        return true;
    }

    #[Override]
    public function isGranted(
        string $path,
        StorageNamespace $namespace,
    ): bool {
        if (!$namespace->isPrivate()) {
            return true;
        }

        return $this->security->isGranted(UserRoles::User->value);
    }
}
