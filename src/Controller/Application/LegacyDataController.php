<?php

declare(strict_types=1);

namespace App\Controller\Application;

use App\Entity\Application\Enums\ImageVariant;
use App\Entity\Photo\Enums\AlbumType;
use App\Entity\Photo\Photo;
use App\Repository\Decision\OrganInformationRepository;
use App\Repository\Photo\AlbumRepository;
use App\Repository\Photo\PhotoRepository;
use App\Security\Photo\PhotoVoter;
use App\Service\Application\ImageUrlBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use function basename;
use function preg_match;
use function sprintf;
use function str_ends_with;

/**
 * Redirects legacy `/data/{path}` URLs onto the new image pipeline. While `public/data/` still exists Caddy serves
 * those files statically and this controller never fires; it becomes the safety net once that directory is removed, so
 * old bookmarks, caches and markdown embeds keep resolving.
 *
 * The migration re-rooted each legacy file under its new namespace directory but kept the filename, so a legacy path is
 * resolved by its filename: `company/{id}/…` is self-identifying and maps deterministically, while a flat
 * `{2ch}/{file}` path is disambiguated against the database. Photo originals are members-only, so they never 301 to an
 * image URL (which would leak the bytes); instead they redirect an authorised member to the viewer and deny
 * everyone else.
 */
final class LegacyDataController extends AbstractController
{
    public function __construct(
        private readonly ImageUrlBuilder $urlBuilder,
        private readonly PhotoRepository $photoRepository,
        private readonly AlbumRepository $albumRepository,
        private readonly OrganInformationRepository $organInformationRepository,
    ) {
    }

    public function resolve(string $path): Response
    {
        // Company assets are self-identifying and scoped, so the new path is deterministic; both logos and banners live
        // under career/{id}/images and are split by requested width, so a mid width serves either.
        if (
            1 === preg_match(
                '#^company/(\d+)/#',
                $path,
                $matches,
            )
        ) {
            return $this->publicRedirect(
                sprintf(
                    'career/%s/images/%s',
                    $matches[1],
                    basename($path),
                ),
                ImageVariant::W960,
            );
        }

        // A flat legacy path does not encode its namespace, so disambiguate by filename against the database. Check the
        // members-only photo original first, so it is never mistaken for a public asset and served.
        $basename = basename($path);

        $photo = $this->photoRepository->findOneByPathBasename($basename);
        if (null !== $photo) {
            return $this->photoRedirect($photo);
        }

        $album = $this->albumRepository->findOneByCoverBasename($basename);
        if (null !== $album) {
            $coverPath = $album->getCoverPath();
            if (null !== $coverPath) {
                return $this->publicRedirect(
                    $coverPath,
                    ImageVariant::Cover,
                );
            }
        }

        $organ = $this->organInformationRepository->findOneByImageBasename($basename);
        if (null !== $organ) {
            $thumbnailPath = $organ->getThumbnailPath();
            if (
                null !== $thumbnailPath
                && str_ends_with(
                    $thumbnailPath,
                    '/' . $basename,
                )
            ) {
                return $this->publicRedirect(
                    $thumbnailPath,
                    ImageVariant::Square,
                );
            }

            $coverPath = $organ->getCoverPath();
            if (null !== $coverPath) {
                return $this->publicRedirect(
                    $coverPath,
                    ImageVariant::W960,
                );
            }
        }

        throw new NotFoundHttpException();
    }

    /**
     * A permanent redirect to the served, cacheable public image URL for a stored path.
     */
    private function publicRedirect(
        string $path,
        ImageVariant $variant,
    ): RedirectResponse {
        return new RedirectResponse(
            $this->urlBuilder->url(
                $path,
                $variant,
            ),
            Response::HTTP_MOVED_PERMANENTLY,
        );
    }

    /**
     * A photo original is members-only: an authorised member is redirected to the album viewer with the photo's
     * deep-link fragment (a temporary, auth-dependent redirect), everyone else is denied rather than served the bytes.
     */
    private function photoRedirect(Photo $photo): Response
    {
        if (
            !$this->isGranted(
                PhotoVoter::VIEW,
                $photo,
            )
        ) {
            throw new AccessDeniedHttpException();
        }

        return $this->redirect(
            $this->generateUrl(
                'photo/album',
                [
                    'type' => AlbumType::Regular->value,
                    'album' => $photo->getAlbum()->getId(),
                ],
            ) . '#pid=' . ($photo->getId() ?? 0),
        );
    }
}
