<?php

namespace Application\Mapper;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\{
    EntityManager,
    EntityRepository,
    OptimisticLockException,
    Exception\ORMException,
};

abstract class BaseMapper
{
    /**
     * Doctrine entity manager.
     *
     * @var EntityManager
     */
    protected EntityManager $em;

    /**
     * Constructor.
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * @param object $entity
     * @throws ORMException
     */
    public function persist(object $entity): void
    {
        $this->em->persist($entity);
        $this->em->flush();
    }

    /**
     * Persist multiple studies.
     *
     * @param array $entities
     * @throws ORMException
     */
    public function persistMultiple(array $entities): void
    {
        foreach ($entities as $entity) {
            $this->em->persist($entity);
        }

        $this->em->flush();
    }

    /**
     * @param object $entity
     * @throws ORMException
     */
    public function remove(object $entity): void
    {
        $this->em->remove($entity);
        $this->em->flush();
    }

    /**
     * Removes multiple studies.
     *
     * @param array $entities
     * @throws ORMException
     */
    public function removeMultiple(array $entities): void
    {
        foreach ($entities as $entity) {
            $this->em->remove($entity);
        }

        $this->em->flush();
    }

    /**
     * Remove an entity by its ID using find
     *
     * @param mixed $id
     * @throws ORMException
     */
    public function removeById(mixed $id): void
    {
        $entity = $this->find($id);
        $this->em->remove($entity);
        $this->em->flush();
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function flush(): void
    {
        $this->em->flush();
    }

    /**
     * Detaches an entity from the entity manager causing any changed to be made to the object to be unsaved
     *
     * @param object $entity
     */
    public function detach(object $entity): void
    {
        $this->em->detach($entity);
    }

    /**
     * Get the entity manager.
     *
     * @return EntityManager
     */
    public function getEntityManager(): EntityManager
    {
        return $this->em;
    }

    /**
     * Get the repository for this mapper.
     *
     * @return EntityRepository
     */
    protected function getRepository(): EntityRepository
    {
        return $this->em->getRepository($this->getRepositoryName());
    }

    /**
     * Get the entity manager connection.
     *
     * @return Connection
     */
    public function getConnection(): Connection
    {
        return $this->em->getConnection();
    }

    /**
     * @param mixed $id The ID of the entity to be retrieved using the primary key
     * @return mixed The entity corresponding to the provided ID or null of the entity cannot be found
     */
    public function find(mixed $id): mixed
    {
        return $this->getRepository()->find($id);
    }

    /**
     * @param mixed $criteria The criteria that describe the entity to be retrieved
     * @return array The entities corresponding to the provided criteria
     */
    public function findBy(mixed $criteria): array
    {
        return $this->getRepository()->findBy($criteria);
    }

    /**
     * @param mixed $criteria The criteria that describe the entity to be retrieved
     * @return mixed The entity corresponding to the provided criteria or null of the entity cannot be found
     */
    public function findOneBy(mixed $criteria): mixed
    {
        return $this->getRepository()->findOneBy($criteria);
    }

    /**
     * @return array All entities in the repository
     */
    public function findAll(): array
    {
        return $this->getRepository()->findAll();
    }

    /**
     * @param mixed $criteria The criteria the objects to be counted should satisfy
     * @return int The number of entities satisfying the criteria
     */
    public function count(mixed $criteria): int
    {
        return $this->getRepository()->count($criteria);
    }

    /**
     * @return string the name of the entity repository
     * e.g. "User/Model/User"
     */
    abstract protected function getRepositoryName(): string;
}
