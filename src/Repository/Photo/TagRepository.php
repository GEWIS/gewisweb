<?php

declare(strict_types=1);

namespace App\Repository\Photo;

use App\Entity\Decision\Member;
use App\Entity\Photo\Photo;
use App\Entity\Photo\Tag;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Tag>
 */
class TagRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            Tag::class,
        );
    }

    public function findTag(
        int $photoId,
        int $lidnr,
    ): ?Tag {
        return $this->findOneBy(
            [
                'photo' => $photoId,
                'member' => $lidnr,
            ],
        );
    }

    /**
     * @return Tag[]
     */
    public function getTagsByLidnr(int $lidnr): array
    {
        return $this->findBy(
            [
                'member' => $lidnr,
            ],
        );
    }

    /**
     * Get all the tags for a photo, but limited to lidnr and full name.
     *
     * @return array<array-key, array{
     *     id: int,
     *     lidnr: int,
     *     fullName: string,
     * }>
     */
    public function getTagsByPhoto(int $photoId): array
    {
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult(
            'id',
            'id',
            'integer',
        )
            ->addScalarResult(
                'lidnr',
                'lidnr',
                'integer',
            )
            ->addScalarResult(
                'fullName',
                'fullName',
            );

        // phpcs:disable Generic.Files.LineLength.TooLong -- no need to split this query more
        $sql = <<<'QUERY'
            SELECT
                `t`.`id`,
                `m`.`lidnr`,
                CONCAT_WS(' ', `m`.`firstName`, IF(LENGTH(`m`.`middleName`), `m`.`middleName`, NULL), `m`.`lastName`) as `fullName`
            FROM `Member` `m`
            LEFT JOIN `Tag` `t` ON `m`.`lidnr` = `t`.`member_id`
            WHERE `t`.`photo_id` = :photo_id
            QUERY;
        // phpcs:enable Generic.Files.LineLength.TooLong

        $query = $this->getEntityManager()->createNativeQuery(
            $sql,
            $rsm,
        );
        $query->setParameter(
            ':photo_id',
            $photoId,
        );

        return $query->getArrayResult();
    }

    /**
     * Get all unique albums a certain member is tagged in
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
     * Returns a recent tag for the member who has the most tags from a set of members.
     *
     * @param Member[] $members
     */
    public function getMostActiveMemberTag(array $members): ?Tag
    {
        $qb = $this->createQueryBuilder('t');

        // Retrieve the lidnr of the member with the most tags
        $qb->select('IDENTITY(t.member), COUNT(t.member) as tag_count')
            ->where('t.member IN (:members)')
            ->setParameter(
                'members',
                $members,
            )
            ->groupBy('t.member')
            ->setMaxResults(1)
            ->orderBy(
                'tag_count',
                'DESC',
            );

        $res = $qb->getQuery()->getResult();

        if ([] === $res) {
            return null;
        }

        $lidnr = $res[0][1];

        // Retrieve the most recent tag of a member
        $qb2 = $this->createQueryBuilder('t');
        $qb2->join(
            Photo::class,
            'p',
            'WITH',
            'p.id = t.photo',
        )
            ->where('t.member = :member')
            ->setParameter(
                'member',
                $lidnr,
            )
            ->setMaxResults(1)
            ->orderBy(
                'p.dateTime',
                'DESC',
            );

        $res = $qb2->getQuery()->getResult();

        if ([] === $res) {
            return null;
        }

        return $res[0];
    }
}
