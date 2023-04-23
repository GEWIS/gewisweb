<?php

declare(strict_types=1);

namespace Photo\Mapper;

use Application\Mapper\BaseMapper;
use Doctrine\ORM\Query\ResultSetMapping;
use Photo\Model\{
    Album as AlbumModel,
    MemberAlbum as MemberAlbumModel,
    Photo as PhotoModel,
};

/**
 * Mappers for Photo.
 *
 * @template-extends BaseMapper<PhotoModel>
 */
class Photo extends BaseMapper
{
    /**
     * Returns all the photos in an album.
     *
     * @param AlbumModel $album The album to retrieve the photos from
     * @param int $start the result to start at
     * @param int|null $maxResults max amount of results to return, null for infinite
     *
     * @return array<array-key, PhotoModel>
     */
    public function getAlbumPhotos(
        AlbumModel $album,
        int $start = 0,
        ?int $maxResults = null,
    ): array {
        $qb = $this->getRepository()->createQueryBuilder('p');

        if ($album instanceof MemberAlbumModel) {
            $qb->innerJoin('p.tags', 't')
                ->where('t.member = ?1')
                ->setParameter(1, $album->getMember());
            // We want to display the photos in a member's album in reversed
            // chronological order
            $qb->setFirstResult($start)
                ->orderBy('p.dateTime', 'DESC');
        } else {
            $qb->where('p.album = ?1')
                ->setParameter(1, $album);
            $qb->setFirstResult($start)
                ->orderBy('p.dateTime', 'ASC');
        }
        if (!is_null($maxResults)) {
            $qb->setMaxResults($maxResults);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Retrieves some random photos from the specified albums. If the amount of available photos is smaller than the
     * requested count, fewer photos will be returned.
     *
     * @param array $albums
     * @param int $maxResults
     *
     * @return PhotoModel[]
     */
    public function getRandomPhotosFromAlbums(
        array $albums,
        int $maxResults,
    ): array {
        $qb = $this->getRepository()->createQueryBuilder('p');
        $qb->where('p.album IN (:album_ids)')
            ->orderBy('RAND()')
            ->setMaxResults($maxResults)
            ->setParameter('album_ids', $albums);

        return $qb->getQuery()->getResult();
    }

    /**
     * Returns the next photo in the album to display.
     *
     * @return PhotoModel|null Photo if there is a next photo, null otherwise
     */
    public function getNextPhoto(
        PhotoModel $photo,
        AlbumModel $album,
    ): ?PhotoModel {
        $qb = $this->getRepository()->createQueryBuilder('p');

        if ($album instanceof MemberAlbumModel) {
            $qb->innerJoin('p.tags', 't')
                ->where('t.member = ?1 AND p.dateTime > ?2')
                ->setParameter(1, $album->getMember())
                ->setParameter(2, $photo->getDateTime());
        } else {
            $qb->where('p.dateTime > ?1 AND p.album = ?2')
                ->setParameter(1, $photo->getDateTime())
                ->setParameter(2, $photo->getAlbum());
        }

        $qb->orderBy('p.dateTime', 'ASC')
            ->setMaxResults(1);
        $res = $qb->getQuery()->getResult();

        return empty($res) ? null : $res[0];
    }

    /**
     * Returns the previous photo in the album to display.
     *
     * @return PhotoModel|null Photo if there is a previous photo, null otherwise
     */
    public function getPreviousPhoto(
        PhotoModel $photo,
        AlbumModel $album,
    ): ?PhotoModel {
        $qb = $this->getRepository()->createQueryBuilder('p');

        if ($album instanceof MemberAlbumModel) {
            $qb->innerJoin('p.tags', 't')
                ->where('t.member = ?1 AND p.dateTime < ?2')
                ->setParameter(1, $album->getMember())
                ->setParameter(2, $photo->getDateTime());
        } else {
            $qb->where('p.dateTime < ?1 AND p.album = ?2')
                ->setParameter(1, $photo->getDateTime())
                ->setParameter(2, $photo->getAlbum());
        }

        $qb->orderBy('p.dateTime', 'DESC')
            ->setMaxResults(1);
        $res = $qb->getQuery()->getResult();

        return empty($res) ? null : $res[0];
    }

    /**
     * Checks if the specified photo exists in the database already and returns it if it does.
     *
     * @param string $path The storage path of the photo
     * @param AlbumModel $album the album the photo is in
     *
     * @return PhotoModel|null
     */
    public function getPhotoByData(
        string $path,
        AlbumModel $album,
    ): ?PhotoModel {
        return $this->getRepository()->findOneBy(
            [
                'path' => $path,
                'album' => $album,
            ]
        );
    }

    protected function getRepositoryName(): string
    {
        return PhotoModel::class;
    }
}
