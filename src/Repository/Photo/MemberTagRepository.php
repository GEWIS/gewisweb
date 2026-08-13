<?php

declare(strict_types=1);

namespace App\Repository\Photo;

use App\Entity\Decision\Member;
use App\Entity\Photo\MemberTag;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\Persistence\ManagerRegistry;

use function array_values;
use function boolval;
use function intval;

/**
 * Repository for {@see MemberTag}s, the member-identifying tag subtype (the GDPR-relevant one).
 *
 * @extends ServiceEntityRepository<MemberTag>
 */
class MemberTagRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            MemberTag::class,
        );
    }

    /**
     * Whether the member is tagged in any photo anywhere in the album's subtree (the album itself or any descendant).
     * This is the recursive form the graduate-subtree fix requires: a graduate tagged in a sub-album may view the
     * parent album, not only the album that directly holds the photo they are in.
     */
    public function isTaggedInAlbumTree(
        int $albumId,
        int $lidnr,
    ): bool {
        $sql = <<<'QUERY'
            WITH RECURSIVE album_tree (id) AS (
                SELECT id FROM Album WHERE id = :album_id
                UNION ALL
                SELECT a.id FROM Album a INNER JOIN album_tree t ON a.parent_id = t.id
            )
            SELECT EXISTS(
                SELECT 1
                FROM Tag tag
                INNER JOIN Photo p ON p.id = tag.photo_id
                WHERE tag.member_id = :lidnr
                  AND p.album_id IN (SELECT id FROM album_tree)
            )
            QUERY;

        return boolval($this->getEntityManager()->getConnection()->fetchOne(
            $sql,
            [
                'album_id' => $albumId,
                'lidnr' => $lidnr,
            ],
        ));
    }

    /**
     * The tag of a given member on a given photo, if any.
     */
    public function findTag(
        int $photoId,
        int $lidnr,
    ): ?MemberTag {
        return $this->findOneBy(
            [
                'photo' => $photoId,
                'member' => $lidnr,
            ],
        );
    }

    /**
     * Which of the given photo ids the member is tagged in, as a set for O(1) lookup — one query for a whole
     * selection instead of a {@see self::findTag} per photo.
     *
     * @param int[] $photoIds
     *
     * @return array<int, true>
     */
    public function findTaggedPhotoIds(
        int $lidnr,
        array $photoIds,
    ): array {
        if ([] === $photoIds) {
            return [];
        }

        $tagged = [];
        foreach (
            $this->createQueryBuilder('t')
                ->select('IDENTITY(t.photo) AS photoId')
                ->where('t.member = :member')
                ->andWhere('t.photo IN (:photos)')
                ->setParameter(
                    'member',
                    $lidnr,
                )
                ->setParameter(
                    'photos',
                    $photoIds,
                )
                ->getQuery()
                ->getScalarResult() as $row
        ) {
            $tagged[intval($row['photoId'])] = true;
        }

        return $tagged;
    }

    /**
     * Whether the member is tagged in at least one photo. Lets their photo page tell "never tagged" apart from
     * "tagged, but every photo hidden from this viewer" in its empty state.
     */
    public function hasTags(int $lidnr): bool
    {
        return null !== $this->findOneBy(['member' => $lidnr]);
    }

    /**
     * Every member tag for a member. Used for that member's GDPR data export.
     *
     * @return MemberTag[]
     */
    public function getTagsByLidnr(int $lidnr): array
    {
        return $this->findBy(['member' => $lidnr]);
    }

    /**
     * The member tags on a photo with their member fetched in the same query, so the viewer overlay can read each
     * tagged member's name without a per-tag lazy load.
     *
     * @return MemberTag[]
     */
    public function findByPhotoWithMember(int $photoId): array
    {
        return $this->createQueryBuilder('t')
            ->addSelect('m')
            ->join(
                't.member',
                'm',
            )
            ->where('t.photo = :photo')
            ->setParameter(
                'photo',
                $photoId,
            )
            ->getQuery()
            ->getResult();
    }

    /**
     * Get all unique albums a certain member is tagged in.
     *
     * @return array<array-key, array{album_id: int}>
     */
    public function getAlbumsByMember(int $lidnr): array
    {
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult(
            'album_id',
            'album_id',
            'integer',
        );

        $sql = <<<'QUERY'
            SELECT DISTINCT
                `p`.`album_id` as `album_id`
            FROM `Photo` `p`
            LEFT JOIN `Tag` `t` ON `t`.`photo_id` = `p`.`id`
            WHERE `t`.`member_id` = :member_id
            QUERY;

        $query = $this->getEntityManager()->createNativeQuery(
            $sql,
            $rsm,
        );
        $query->setParameter(
            ':member_id',
            $lidnr,
        );

        return $query->getArrayResult();
    }

    /**
     * The newest photo each of these members is tagged in, at most one per member. A member nobody has ever tagged
     * simply does not appear, which is what lets the birthday panel rotate between the members it can show rather
     * than showing the same one all day.
     *
     * @param Member[] $members
     *
     * @return MemberTag[]
     */
    public function findMostRecentTagPerMember(array $members): array
    {
        if ([] === $members) {
            return [];
        }

        // A groupwise max in one round trip, instead of a query per member.
        /** @var list<MemberTag> $candidates */
        $candidates = $this->createQueryBuilder('t')
            ->addSelect('p')
            ->join(
                't.photo',
                'p',
            )
            ->where('t.member IN (:members)')
            ->andWhere(
                'p.dateTime = (SELECT MAX(p2.dateTime) FROM ' . MemberTag::class . ' t2'
                . ' JOIN t2.photo p2 WHERE t2.member = t.member)',
            )
            ->setParameter(
                'members',
                $members,
            )
            ->getQuery()
            ->getResult();

        // Two photos can share a timestamp; a member still gets at most one tag.
        $tags = [];
        foreach ($candidates as $candidate) {
            $lidnr = $candidate->getMember()->getLidnr();
            if (isset($tags[$lidnr])) {
                continue;
            }

            $tags[$lidnr] = $candidate;
        }

        return array_values($tags);
    }
}
