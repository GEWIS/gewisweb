<?php

declare(strict_types=1);

namespace Photo\Mapper;

use Application\Mapper\BaseMapper;
use Decision\Model\Member as MemberModel;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\ResultSetMapping;
use Photo\Model\BodyTag as BodyTagModel;
use Photo\Model\MemberTag as MemberTagModel;
use Photo\Model\Photo as PhotoModel;
use Photo\Model\Tag as TagModel;

/**
 * Mappers for Tags.
 *
 * @template-extends BaseMapper<TagModel>
 */
class Tag extends BaseMapper
{
    /**
     * @psalm-param 'body'|'member' $type
     */
    public function findTag(
        int $photoId,
        string $type,
        int $id,
    ): ?TagModel {
        if ('body' === $type) {
            $repository = $this->getEntityManager()->getRepository(BodyTagModel::class);
        } else {
            $repository = $this->getEntityManager()->getRepository(MemberTagModel::class);
        }

        return $repository->findOneBy(
            [
                'photo' => $photoId,
                $type => $id,
            ],
        );
    }

    /**
     * @return TagModel[]
     */
    public function getTagsByLidnr(int $lidnr): array
    {
        return $this->getRepository()->findBy(
            [
                'member' => $lidnr,
            ],
        );
    }

    /**
     * Get all the tags for a photo, including lidnr and full name for members, or id, abbr, and type for bodies.
     *
     * @return array<array-key, array{
     *     id: int,
     *     tagged_id: int,
     *     tagged_name: string,
     *     tagged_type: string,
     *     body_type: ?string,
     * }>
     */
    public function getTagsByPhoto(int $photoId): array
    {
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('id', 'id', 'integer')
            ->addScalarResult('tagged_id', 'tagged_id', 'integer')
            ->addScalarResult('tagged_name', 'tagged_name')
            ->addScalarResult('tagged_type', 'tagged_type')
            ->addScalarResult('body_type', 'body_type');

        // phpcs:disable Generic.Files.LineLength.TooLong -- no need to split this query more
        $sql = <<<'QUERY'
            SELECT
                `t`.`id`,
                CASE
                    WHEN `t`.`type` = 'member' THEN `m`.`lidnr`
                    WHEN `t`.`type` = 'body' THEN `b`.`id`
                END AS `tagged_id`,
                CASE
                    WHEN `t`.`type` = 'member' THEN CONCAT_WS(' ', `m`.`firstName`, IF(LENGTH(`m`.`middleName`), `m`.`middleName`, NULL), `m`.`lastName`)
                    WHEN `t`.`type` = 'body' THEN `b`.`abbr`
                END AS `tagged_name`,
                `t`.`type` AS `tagged_type`,
                CASE
                    WHEN `t`.`type` = 'body' THEN `b`.`type`
                    ELSE NULL
                END AS `body_type`
            FROM `Tag` `t`
            LEFT JOIN `Member` `m` ON `t`.`member_id` = `m`.`lidnr` AND `t`.`type` = 'member'
            LEFT JOIN `Organ` `b` ON `t`.`body_id` = `b`.`id` AND `t`.`type` = 'body'
            WHERE `t`.`photo_id` = :photo_id
            QUERY;
        // phpcs:enable Generic.Files.LineLength.TooLong

        $query = $this->getEntityManager()->createNativeQuery($sql, $rsm);
        $query->setParameter(':photo_id', $photoId);

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
        $rsm->addScalarResult('album_id', 'album_id', 'integer');

        $sql = <<<'QUERY'
            SELECT DISTINCT
                `p`.`album_id` as `album_id`
            FROM `Photo` `p`
            LEFT JOIN `Tag` `t` ON `t`.`photo_id` = `p`.`id`
            WHERE `t`.`member_id` = :member_id
            AND `t`.`body_id` IS NULL
            QUERY;

        $query = $this->getEntityManager()->createNativeQuery($sql, $rsm);
        $query->setParameter(':member_id', $lidnr);

        return $query->getArrayResult();
    }

    /**
     * Returns a recent tag for the member who has the most tags from a set of members.
     *
     * @param MemberModel[] $members
     */
    public function getMostActiveMemberTag(array $members): ?TagModel
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        // Retrieve the lidnr of the member with the most tags
        $qb->select('IDENTITY(t.member), COUNT(t.member) as tag_count')
            ->from(MemberTagModel::class, 't')
            ->where('t.member IN (?1)')
            ->setParameter(1, $members)
            ->groupBy('t.member')
            ->setMaxResults(1)
            ->orderBy('tag_count', 'DESC');

        $res = $qb->getQuery()->getResult();

        if (empty($res)) {
            return null;
        }

        $lidnr = $res[0][1];

        // Retrieve the most recent tag of a member
        $qb2 = $this->getRepository()->createQueryBuilder('t');
        $qb2->join(PhotoModel::class, 'p', 'WITH', 'p.id = t.photo')
            ->where('t.member = ?1')
            ->setParameter(1, $lidnr)
            ->setMaxResults(1)
            ->orderBy('p.dateTime', 'DESC');

        $res = $qb2->getQuery()->getResult();

        if (empty($res)) {
            return null;
        }

        return $res[0];
    }

    protected function getRepositoryName(): string
    {
        return TagModel::class;
    }
}
