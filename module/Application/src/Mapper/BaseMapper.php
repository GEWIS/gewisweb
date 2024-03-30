<?php

declare(strict_types=1);

namespace Application\Mapper;

use Application\Model\LocalisedText as LocalisedTextModel;
use Closure;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;

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
     * @throws EntityNotFoundException
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function removeById(mixed $id): void
    {
        $entity = $this->find($id);
        if (null === $entity) {
            throw new EntityNotFoundException('No entity with the given ID could be found.');
        }

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
     * @psalm-param T $entity
     */
    public function detach(object $entity): void
    {
        $this->em->detach($entity);
    }

    /**
     * Get the entity manager.
     */
    public function getEntityManager(): EntityManager
    {
        return $this->em;
    }

    /**
     * Get the repository for this mapper.
     *
     * @psalm-return EntityRepository<T>
     */
    protected function getRepository(): EntityRepository
    {
        return $this->em->getRepository($this->getRepositoryName());
    }

    /**
     * Get the entity manager connection.
     */
    public function getConnection(): Connection
    {
        return $this->em->getConnection();
    }

    /**
     * @param mixed $id The ID of the entity to be retrieved using the primary key
     *
     * @return T|null The entity corresponding to the provided ID or null of the entity cannot be found
     */
    public function find(mixed $id): ?object
    {
        return $this->getRepository()->find($id);
    }

    /**
     * @param mixed $criteria The criteria that describe the entity to be retrieved
     *
     * @return T[] The entities corresponding to the provided criteria
     */
    public function findBy(mixed $criteria): array
    {
        return $this->getRepository()->findBy($criteria);
    }

    /**
     * @param array $criteria The criteria that describe the entity to be retrieved
     *
     * @return T|null The entity corresponding to the provided criteria or null of the entity cannot be found
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    public function findOneBy(array $criteria): ?object
    {
        return $this->getRepository()->findOneBy($criteria);
    }

    /**
     * @return T[] All entities in the repository
     */
    public function findAll(): array
    {
        return $this->getRepository()->findAll();
    }

    /**
     * @param mixed $criteria The criteria the objects to be counted should satisfy
     *
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
     */
    public function transactional(Closure $func): mixed
    {
        return $this->getEntityManager()->wrapInTransaction(
            function () use ($func) {
                return $func($this);
            },
        );
    }

    /**
     * @return string the name of the entity repository e.g. "User/Model/User"
     * @psalm-return class-string<T>
     */
    abstract protected function getRepositoryName(): string;
}
