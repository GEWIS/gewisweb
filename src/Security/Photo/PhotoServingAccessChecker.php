<?php

declare(strict_types=1);

namespace App\Security\Photo;

use App\Entity\Application\Enums\StorageNamespace;
use App\Entity\User\Enums\UserRoles;
use App\Repository\Photo\AlbumRepository;
use App\Repository\Photo\PhotoRepository;
use App\Security\Application\ServingAccessCheckerInterface;
use Override;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * The serving access check for the members-only album-photo and album-cover namespaces, taking precedence over
 * {@see DefaultServingAccessChecker}. A cover is a mosaic of members-only photos, so it is gated the same way.
 *
 * Full members (and API users / the TV screens) get the fast path: the day-signature the controller already validated
 * proves the URL came from a context where fine-grained authorization ran, so no query is needed. Graduates do not get
 * the fast path: a leaked URL must never bypass their #1658 membership cutoff, so their request runs the {@see
 * PhotoVoter} on the photo (originals) or the {@see AlbumVoter} on the album (covers), resolved from the stored path.
 * Everyone else (anonymous, company) is denied.
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
        private AlbumRepository $albumRepository,
    ) {
    }

    #[Override]
    public function supports(StorageNamespace $namespace): bool
    {
        return StorageNamespace::PhotoOriginal === $namespace
            || StorageNamespace::PhotoCover === $namespace;
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

        // Graduates: enforce the #1658 cutoff even on a leaked URL, against the photo or the cover's album.
        if ($this->security->isGranted(UserRoles::Graduate->value)) {
            return StorageNamespace::PhotoCover === $namespace
                ? $this->graduateMayViewCover($path)
                : $this->graduateMayViewPhoto($path);
        }

        // Anonymous and company users cannot fetch members-only images.
        return false;
    }

    private function graduateMayViewPhoto(string $path): bool
    {
        $photo = $this->photoRepository->findOneBy(['path' => $path]);

        return null !== $photo
            && $this->security->isGranted(
                PhotoVoter::VIEW,
                $photo,
            );
    }

    private function graduateMayViewCover(string $path): bool
    {
        $album = $this->albumRepository->findOneBy(['coverPath' => $path]);

        return null !== $album
            && $this->security->isGranted(
                AlbumVoter::VIEW,
                $album,
            );
    }
}
