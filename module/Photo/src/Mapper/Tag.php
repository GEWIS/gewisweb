<?php

declare(strict_types=1);

namespace Photo\Mapper;

use Application\Mapper\BaseMapper;
use Decision\Model\Member as MemberModel;
use Doctrine\ORM\Query\ResultSetMapping;
use Photo\Model\Photo as PhotoModel;
use Photo\Model\Tag as TagModel;

/**
 * Mappers for Tags.
 *
 * @template-extends BaseMapper<TagModel>
 */
class Tag extends BaseMapper
{
    public function findTag(
        int $photoId,
        int $lidnr,
    ): ?TagModel {
        return $this->getRepository()->findOneBy(
            [
                'photo' => $photoId,
                'member' => $lidnr,
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
        $rsm->addScalarResult('id', 'id', 'integer')
            ->addScalarResult('lidnr', 'lidnr', 'integer')
            ->addScalarResult('fullName', 'fullName');

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
            ->from($this->getRepositoryName(), 't')
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
