<?php

declare(strict_types=1);

namespace Application\Mapper;

use Application\Model\LocalisedText as LocalisedTextModel;
use Closure;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\{
    EntityManager,
    EntityNotFoundException,
    EntityRepository,
    OptimisticLockException,
    Exception\ORMException,
};

/**
 * The base mapper to be used for all other mappers. It helps with preventing duplicate Doctrine code. It uses special
 * types to ensure that the returned values are as expected. As {@link LocalisedTextModel} does not have its own mapper,
 * any of such objects can be persisted and/or removed from the base mapper.
 *
 * @template T of object
 */
abstract class BaseMapper
{
    public function __construct(private readonly EntityManager $em)
    {
    }

    /**
     * @param object $entity
     * @psalm-param T|LocalisedTextModel $entity
     *
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
     * @psalm-param T[] $entities
     *
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
     * @psalm-param T|LocalisedTextModel $entity
     *
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
     * @psalm-param T[] $entities
     *
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
     * @throws EntityNotFoundException
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function removeById(mixed $id): void
    {
        $entity = $this->find($id);
        if (!is_null($entity)) {
            $this->em->remove($entity);
            $this->em->flush();
        } else {
            throw new EntityNotFoundException('No entity with the given ID could be found.');
        }
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
     * @psalm-param T $entity
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
     * @psalm-return EntityRepository<T>
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
     * @return null|object The entity corresponding to the provided ID or null of the entity cannot be found
     * @psalm-return T|null
     */
    public function find(mixed $id): null|object
    {
        return $this->getRepository()->find($id);
    }

    /**
     * @param mixed $criteria The criteria that describe the entity to be retrieved
     * @return array The entities corresponding to the provided criteria
     * @psalm-return T[]
     */
    public function findBy(mixed $criteria): array
    {
        return $this->getRepository()->findBy($criteria);
    }

    /**
     * @param array $criteria The criteria that describe the entity to be retrieved
     * @return null|object The entity corresponding to the provided criteria or null of the entity cannot be found
     * @psalm-return T|null
     */
    public function findOneBy(array $criteria): null|object
    {
        return $this->getRepository()->findOneBy($criteria);
    }

    /**
     * @return array All entities in the repository
     * @psalm-return T[]
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
     * Transactional Doctrine wrapper.
     *
     * Instead of the EntityManager, this inserts this Mapper into the
     * function.
     *
     * @param Closure $func
     *
     * @return mixed
     */
    public function transactional(Closure $func): mixed
    {
        return $this->getEntityManager()->wrapInTransaction(
            function ($em) use ($func) {
                return $func($this);
            }
        );
    }

    /**
     * @return string the name of the entity repository e.g. "User/Model/User"
     * @psalm-return class-string<T>
     */
    abstract protected function getRepositoryName(): string;
}
