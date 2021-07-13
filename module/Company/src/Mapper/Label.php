<?php

namespace Company\Mapper;

use Company\Model\JobLabel as LabelModel;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

/**
 * Mappers for labels.
 */
class Label
{
    /**
     * Doctrine entity manager.
     *
     * @var EntityManager
     */
    protected $em;

    /**
     * Constructor.
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function persist($label)
    {
        $this->em->persist($label);
        $this->em->flush();
    }

    /**
     * Saves all labels.
     */
    public function save()
    {
        $this->em->flush();
    }

    /**
     * Finds the label with the given slug.
     *
     * @param int $labelSlug
     */
    public function findLabel($labelSlug)
    {
        return $this->getRepository()->findOneBy(['slug' => $labelSlug]);
    }

    /**
     * Finds the label with the given id.
     *
     * @param int $labelId
     */
    public function findLabelById($labelId)
    {
        return $this->getRepository()->findOneBy(['id' => $labelId]);
    }

    public function findVisibleLabelByLanguage($labelLanguage)
    {
        $objectRepository = $this->getRepository(); // From clause is integrated in this statement
        $qb = $objectRepository->createQueryBuilder('c')
            ->select('c')->where('c.language=:lang')
            ->setParameter('lang', $labelLanguage);

        return $qb->getQuery()->getResult();
    }

    /**
     * Find the same label, but in the given language.
     */
    public function siblingLabel($label, $lang)
    {
        $objectRepository = $this->getRepository(); // From clause is integrated in this statement
        $qb = $objectRepository->createQueryBuilder('c');
        $qb->select('c')
            ->where('c.languageNeutralId=:labelId')
            ->andWhere('c.language=:language')
            ->setParameter('labelId', $label->getLanguageNeutralId())
            ->setParameter('language', $lang);

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function findAllLabelsById($labelId)
    {
        $objectRepository = $this->getRepository(); // From clause is integrated in this statement
        $qb = $objectRepository->createQueryBuilder('c')
            ->select('c')->where('c.languageNeutralId=:labelId')
            ->setParameter('labelId', $labelId);

        return $qb->getQuery()->getResult();
    }

    /**
     * Deletes the given label.
     *
     * @param LabelModel $label
     */
    public function delete($label)
    {
        $this->em->remove($label);
        $this->em->flush();
    }

    /**
     * Find all Labels.
     *
     * @return Collection
     */
    public function findAll()
    {
        return $this->getRepository()->findAll();
    }

    /**
     * Get the repository for this mapper.
     *
     * @return EntityRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('Company\Model\JobLabel');
    }
}
