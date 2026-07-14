<?php

declare(strict_types=1);

namespace App\Security\Photo;

use App\Entity\Application\Enums\StorageNamespace;
use App\Entity\User\Enums\UserRoles;
use App\Repository\Photo\PhotoRepository;
use App\Security\Application\ServingAccessCheckerInterface;
use Override;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * The serving access check for the private album-photos namespace, taking precedence over
 * {@see DefaultServingAccessChecker}.
 *
 * Full members (and API users / the TV screens) get the fast path: the day-signature the controller already validated
 * proves the URL came from a context where fine-grained authorization ran, so no per-photo query is needed. Graduates
 * do not get the fast path: a leaked URL must never bypass their #1658 membership cutoff, so their request runs the
 * per-photo {@see PhotoVoter}, resolving the photo by its stored path. Everyone else (anonymous, company) is denied.
 */
#[AutoconfigureTag(
    'app.serving_access_checker',
    ['priority' => 0],
)]
final readonly class PhotoServingAccessChecker implements ServingAccessCheckerInterface
{
    public function __construct(
        private Security $security,
        private PhotoRepository $photoRepository,
    ) {
    }

    #[Override]
    public function supports(StorageNamespace $namespace): bool
    {
        return StorageNamespace::PhotoOriginal === $namespace;
    }

    #[Override]
    public function isGranted(
        string $path,
        StorageNamespace $namespace,
    ): bool {
        // Full members (ROLE_MEMBER excludes graduates) and API users: the validated signature suffices.
        if (
            $this->security->isGranted(UserRoles::Member->value)
            || $this->security->isGranted(UserRoles::ApiUser->value)
        ) {
            return true;
        }

        // Graduates: enforce the per-photo cutoff even on a leaked URL.
        if ($this->security->isGranted(UserRoles::Graduate->value)) {
            $photo = $this->photoRepository->findOneBy(['path' => $path]);
            if (null === $photo) {
                return false;
            }

            return $this->security->isGranted(
                PhotoVoter::VIEW,
                $photo,
            );
        }

        // Anonymous and company users cannot fetch members-only photos.
        return false;
    }
}
