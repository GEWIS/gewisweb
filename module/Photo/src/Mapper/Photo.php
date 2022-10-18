<?php

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
     * @return array of photo's
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
     * Retrieves some random photos from the specified album and its sub-albums (recursively). If the amount of
     * available photos is smaller than the requested count, fewer photos will be returned.
     *
     * Note: `$depth` is the amount of sub-albums (recursively) inclusively to be included, `$album` is at a depth of
     * zero (0). Example for a depth of 2:
     *
     * root (0):
     *     photos
     *     sub-album-1 (1):
     *         [photos]
     *     sub-album-2 (1):
     *         [photos]
     *         sub-sub-album-1 (2):
     *             [photos]
     *
     * @param AlbumModel $album
     * @param int $maxResults
     *
     * @return PhotoModel[]
     */
    public function getRandomAlbumPhotos(
        AlbumModel $album,
        int $maxResults,
        int $depth = 2,
    ): array {
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('photo_id', 'photo_id', 'integer');

        $sql = <<<QUERY
            WITH RECURSIVE `subAlbumContents` (`depth`, `id`, `photo_id`) AS (
                SELECT 0 AS `depth`, `a`.`id`, `p`.`id` AS `photo_id`
                FROM `Album` `a`
                LEFT JOIN `Photo` `p` ON `p`.`album_id` = `a`.`id`
                WHERE `a`.`id` = :album_id

                UNION ALL

                SELECT `s`.`depth` + 1 AS `depth`, `a`.`id`, `p`.`id` AS `photo_id`
                FROM `Album` `a`
                LEFT JOIN `Photo` `p` ON `p`.`album_id` = `a`.`id`
                INNER JOIN `subAlbumContents` `s` ON `s`.`id` = `a`.`parent_id`
                WHERE `depth` < :depth
            )
            SELECT `photo_id`
            FROM `subAlbumContents`
            WHERE `photo_id` IS NOT NULL
                AND `depth` = (
                    SELECT MIN(`depth`)
                    FROM `subAlbumContents`
                    WHERE `photo_id` IS NOT NULL
                )
            ORDER BY RAND()
            LIMIT :limit;
            QUERY;

        $query = $this->getEntityManager()->createNativeQuery($sql, $rsm);
        $query->setParameter('album_id', $album->getId())
            ->setParameter('limit', $maxResults)
            ->setParameter('depth', $depth);

        $result = $query->getArrayResult();
        $photos = [];
        foreach ($result as $photo) {
            $photos[] = $this->find($photo['photo_id']);
        }

        return $photos;
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
