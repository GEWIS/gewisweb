<?php

declare(strict_types=1);

namespace Photo\Mapper;

use Application\Mapper\BaseMapper;
use Decision\Model\Member as MemberModel;
use Override;
use Photo\Model\Album as AlbumModel;
use Photo\Model\MemberAlbum as MemberAlbumModel;
use Photo\Model\Photo as PhotoModel;

use function addcslashes;

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
     * @param AlbumModel $album      The album to retrieve the photos from
     * @param int        $start      the result to start at
     * @param int|null   $maxResults max amount of results to return, null for infinite
     *
     * @return PhotoModel[]
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

        if (null !== $maxResults) {
            $qb->setMaxResults($maxResults);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Retrieves some random photos from the specified albums. If the amount of available photos is smaller than the
     * requested count, fewer photos will be returned.
     *
     * @param AlbumModel[] $albums
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
     * Checks if the specified photo exists in the database already and returns it if it does.
     *
     * @param string     $path  The storage path of the photo
     * @param AlbumModel $album the album the photo is in
     */
    public function getPhotoByData(
        string $path,
        AlbumModel $album,
    ): ?PhotoModel {
        return $this->getRepository()->findOneBy(
            [
                'path' => $path,
                'album' => $album,
            ],
        );
    }

    /**
     * Get all photos that have the member as its author.
     *
     * @return PhotoModel[]
     */
    public function findPhotosByMember(MemberModel $member): array
    {
        $qb = $this->getRepository()->createQueryBuilder('p');
        $qb->where('p.artist LIKE :full_name')
            ->setParameter('full_name', '%' . addcslashes($member->getFullName(), '%_') . '%');

        return $qb->getQuery()->getResult();
    }

    #[Override]
    protected function getRepositoryName(): string
    {
        return PhotoModel::class;
    }
}
