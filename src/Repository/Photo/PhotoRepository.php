<?php

declare(strict_types=1);

namespace App\Repository\Photo;

use App\Entity\Decision\Member;
use App\Entity\Photo\Album;
use App\Entity\Photo\MemberAlbum;
use App\Entity\Photo\MemberTag;
use App\Entity\Photo\OrganAlbum;
use App\Entity\Photo\OrganTag;
use App\Entity\Photo\Photo;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

use function addcslashes;

/**
 * @extends ServiceEntityRepository<Photo>
 */
class PhotoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            Photo::class,
        );
    }

    /**
     * Returns all the photos in an album.
     *
     * @param Album    $album      The album to retrieve the photos from
     * @param int      $start      the result to start at
     * @param int|null $maxResults max amount of results to return, null for infinite
     *
     * @return Photo[]
     */
    public function getAlbumPhotos(
        Album $album,
        int $start = 0,
        ?int $maxResults = null,
    ): array {
        // weeklyPhoto is an inverse one-to-one, which Doctrine would otherwise load with a separate query per photo;
        // fetch-joining it keeps browsing an album to a single query.
        $qb = $this->createQueryBuilder('p')
            ->leftJoin(
                'p.weeklyPhoto',
                'weeklyPhoto',
            )
            ->addSelect('weeklyPhoto');

        if ($album instanceof MemberAlbum) {
            // Member tags moved onto the MemberTag subtype, so join it explicitly rather than the base `p.tags`.
            $qb->innerJoin(
                MemberTag::class,
                't',
                'WITH',
                't.photo = p AND t.member = :member',
            )
                ->setParameter(
                    'member',
                    $album->getMember(),
                );
            // We want to display the photos in a member's album in reversed
            // chronological order
            $qb->setFirstResult($start)
                ->orderBy(
                    'p.dateTime',
                    'DESC',
                );
        } elseif ($album instanceof OrganAlbum) {
            // Organ tags are their own subtype as well; join it so a body album is the photos that organ is tagged in.
            $qb->innerJoin(
                OrganTag::class,
                't',
                'WITH',
                't.photo = p AND t.organ = :organ',
            )
                ->setParameter(
                    'organ',
                    $album->getOrgan(),
                );
            $qb->setFirstResult($start)
                ->orderBy(
                    'p.dateTime',
                    'DESC',
                );
        } else {
            $qb->where('p.album = :album')
                ->setParameter(
                    'album',
                    $album,
                );
            $qb->setFirstResult($start)
                ->orderBy(
                    'p.dateTime',
                    'ASC',
                );
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
     * @param Album[] $albums
     *
     * @return Photo[]
     */
    public function getRandomPhotosFromAlbums(
        array $albums,
        int $maxResults,
    ): array {
        $qb = $this->createQueryBuilder('p');
        $qb->where('p.album IN (:album_ids)')
            ->orderBy('RAND()')
            ->setMaxResults($maxResults)
            ->setParameter(
                'album_ids',
                $albums,
            );

        return $qb->getQuery()->getResult();
    }

    /**
     * Checks if the specified photo exists in the database already and returns it if it does.
     *
     * @param string $path  The storage path of the photo
     * @param Album  $album the album the photo is in
     */
    public function getPhotoByData(
        string $path,
        Album $album,
    ): ?Photo {
        return $this->findOneBy(
            [
                'path' => $path,
                'album' => $album,
            ],
        );
    }

    /**
     * Resolve a set of photo ids to their {@see Photo} entities. Shared by the member and admin bulk actions so the id
     * resolution (and any future capping or access filtering) lives in one place. An empty id list issues no query.
     *
     * @param int[] $ids
     *
     * @return Photo[]
     */
    public function findByIds(array $ids): array
    {
        return [] === $ids
            ? []
            : $this->findBy(['id' => $ids]);
    }

    /**
     * The number of direct photos each of the given albums has, keyed by album id, in a single query — so a grid of
     * album cards does not issue one `COUNT(*) ... WHERE album_id = ?` per card. Albums with no photos are absent.
     *
     * @param Album[] $albums
     *
     * @return array<int, int>
     */
    public function getDirectPhotoCounts(array $albums): array
    {
        if ([] === $albums) {
            return [];
        }

        $counts = [];
        foreach (
            $this->createQueryBuilder('p')
                ->select(
                    'IDENTITY(p.album) AS albumId',
                    'COUNT(p.id) AS total',
                )
                ->where('p.album IN (:albums)')
                ->setParameter(
                    'albums',
                    $albums,
                )
                ->groupBy('p.album')
                ->getQuery()
                ->getScalarResult() as $row
        ) {
            $counts[(int) $row['albumId']] = (int) $row['total'];
        }

        return $counts;
    }

    /**
     * The photo whose stored path ends with the given filename, used to resolve a legacy `/data/{2ch}/{file}` URL onto
     * the migrated photo (whose path re-roots that same filename under its album). Filenames are content-hashed, so a
     * match is unambiguous.
     */
    public function findOneByPathBasename(string $basename): ?Photo
    {
        return $this->createQueryBuilder('photo')
            ->where('photo.path LIKE :suffix')
            ->setParameter(
                'suffix',
                '%/' . addcslashes(
                    $basename,
                    '%_',
                ),
            )
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Get all photos that have the member as its author.
     *
     * @return Photo[]
     */
    public function findPhotosByMember(Member $member): array
    {
        $qb = $this->createQueryBuilder('p');
        $qb->where('p.artist LIKE :full_name')
            ->setParameter(
                'full_name',
                '%' . addcslashes(
                    $member->getFullName(),
                    '%_',
                ) . '%',
            );

        return $qb->getQuery()->getResult();
    }
}
