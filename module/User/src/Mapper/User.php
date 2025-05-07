<?php

declare(strict_types=1);

namespace User\Mapper;

use Application\Mapper\BaseMapper;
use DateTime;
use Decision\Model\Member as MemberModel;
use Override;
use User\Model\User as UserModel;

use function is_numeric;
use function strtolower;

/**
 * @template-extends BaseMapper<UserModel>
 */
class User extends BaseMapper
{
    /**
     * Find a user by its login.
     */
    public function findByLogin(string $login): ?UserModel
    {
        // create query for user
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('u, r, m')
            ->from($this->getRepositoryName(), 'u')
            ->leftJoin('u.roles', 'r')
            ->join('u.member', 'm');

        // depending on login, add correct where clause
        if (is_numeric($login)) {
            $qb->where('u.lidnr = ?1');
        } else {
            $qb->where('LOWER(m.email) = ?1');
        }

        // set the parameters
        $qb->setParameter(1, strtolower($login));
        $qb->setMaxResults(1);

        $res = $qb->getQuery()->getResult();

        return empty($res) ? null : $res[0];
    }

    /**
     * Used for password resets, does not include members who are hidden, expired, and/or deleted. These requirements
     * are also used during the login process.
     */
    public function findForReset(
        string $email,
        int $lidnr,
    ): ?UserModel {
        $qb = $this->getRepository()->createQueryBuilder('u');
        $qb->innerJoin(MemberModel::class, 'm', 'WITH', 'u.lidnr = m.lidnr')
            ->where('u.lidnr = :lidnr')
            ->andWhere('LOWER(m.email) = :email')
            ->andWhere('m.deleted = :false')
            ->andWhere('m.hidden = :false')
            ->andWhere('m.expiration > :now');

        $qb->setParameter('lidnr', $lidnr)
            ->setParameter('email', strtolower($email))
            ->setParameter('false', false)
            ->setParameter('now', new DateTime('now'));

        return $qb->getQuery()->getOneOrNullResult();
    }

    #[Override]
    protected function getRepositoryName(): string
    {
        return UserModel::class;
    }
}
