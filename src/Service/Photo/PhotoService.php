<?php

declare(strict_types=1);

namespace App\Service\Photo;

use App\Entity\Application\Enums\ImageVariant;
use App\Entity\Photo\Album;
use App\Entity\Photo\Photo;
use App\Repository\Photo\PhotoRepository;
use App\Service\Application\ImageUrlBuilder;
use App\ViewModel\Photo\ManifestEntry;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use function round;

/**
 * Read access to individual photos for the browsing pages and the viewer.
 */
final readonly class PhotoService
{
    /** The large variant's width is used as the viewer's reference size (see {@see ManifestEntry}). */
    private const int REFERENCE_WIDTH = 1920;

    public function __construct(
        private ImageUrlBuilder $imageUrlBuilder,
        private UrlGeneratorInterface $urlGenerator,
        private PhotoRepository $photoRepository,
    ) {
    }

    /**
     * The viewer manifest for an album: one entry per direct photo, in the album's photo order, each with signed thumb,
     * large and extra-large variant URLs and a download URL. Access to the album itself is checked by the caller.
     *
     * @return list<ManifestEntry>
     */
    public function getAlbumManifest(Album $album): array
    {
        $entries = [];
        // Fetch through the repository (rather than the lazy collection) so the photos and their weekly-photo relation
        // load in a single query, and in the same order as the thumbnail grid.
        foreach ($this->photoRepository->getAlbumPhotos($album) as $photo) {
            $entries[] = $this->manifestEntry($photo);
        }

        return $entries;
    }

    /**
     * The viewer manifest for a virtual weekly album: one entry per photo of the week, each carrying a deep link to its
     * real album so the viewer can offer a "go to the original album" button. Access is checked by the caller.
     *
     * @param Photo[] $photos
     *
     * @return list<ManifestEntry>
     */
    public function getWeeklyManifest(array $photos): array
    {
        $entries = [];
        foreach ($photos as $photo) {
            $entries[] = $this->manifestEntry(
                $photo,
                $this->urlGenerator->generate(
                    'photo/album',
                    [
                        'type' => 'album',
                        'album' => (int) $photo->getAlbum()->getId(),
                    ],
                ) . '#pid=' . (int) $photo->getId(),
            );
        }

        return $entries;
    }

    private function manifestEntry(
        Photo $photo,
        ?string $albumUrl = null,
    ): ManifestEntry {
        $path = $photo->getPath();
        // aspectRatio is height/width; a missing one falls back to square so the viewer still gets usable dimensions.
        $aspectRatio = $photo->getAspectRatio() ?? 1.0;

        return new ManifestEntry(
            id: (int) $photo->getId(),
            w: self::REFERENCE_WIDTH,
            h: (int) round((float) self::REFERENCE_WIDTH * $aspectRatio),
            thumbUrl: $this->imageUrlBuilder->url(
                $path,
                ImageVariant::W640,
            ),
            largeUrl: $this->imageUrlBuilder->url(
                $path,
                ImageVariant::W1920,
            ),
            xlargeUrl: $this->imageUrlBuilder->url(
                $path,
                ImageVariant::W2560,
            ),
            // The download always serves from the photo's own album (for a weekly album that differs from the album
            // being viewed).
            downloadUrl: $this->urlGenerator->generate(
                'photo/download',
                [
                    'album' => (int) $photo->getAlbum()->getId(),
                    'photo' => (int) $photo->getId(),
                ],
            ),
            albumUrl: $albumUrl,
        );
    }
}
