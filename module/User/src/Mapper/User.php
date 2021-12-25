<?php

namespace User\Mapper;

use Application\Mapper\BaseMapper;
use User\Model\NewUser as NewUserModel;
use User\Model\User as UserModel;

class User extends BaseMapper
{
    /**
     * Find a user by its membership number.
     *
     * @param int $lidnr Membership number
     *
     * @return UserModel|null
     */
    public function findByLidnr(int $lidnr): ?UserModel
    {
        return $this->getRepository()->findOneBy(['lidnr' => $lidnr]);
    }

    /**
     * Find a user by its login.
     *
     * @param string $login
     *
     * @return UserModel|null
     */
    public function findByLogin(string $login): ?UserModel
    {
        // create query for user
        $qb = $this->em->createQueryBuilder();
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
     * Finish user creation.
     *
     * This will both destroy the NewUser and create the given user
     *
     * @param UserModel $user User to create
     * @param NewUserModel $newUser NewUser to destroy
     */
    public function createUser(
        UserModel $user,
        NewUserModel $newUser,
    ): void {
        $this->em->persist($user);
        $this->em->remove($newUser);
        $this->em->flush();
    }

    /**
     * @inheritDoc
     */
    protected function getRepositoryName(): string
    {
        return UserModel::class;
    }
}
