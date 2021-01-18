<?php

namespace Company\Mapper;

use Company\Model\JobLabel as LabelModel;
use Doctrine\ORM\EntityManager;

/**
 * Mappers for labels.
 *
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
     *
     * @param EntityManager $em
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
     * Saves all labels
     *
     */
    public function save()
    {
        $this->em->flush();
    }

    /**
     * Finds the label with the given slug
     *
     * @param integer $labelSlug
     */
    public function findLabel($labelSlug)
    {
        return $this->getRepository()->findOneBy(['slug' => $labelSlug]);
    }

    /**
     * Finds the label with the given id
     *
     * @param integer $labelId
     */
    public function findLabelById($labelId)
    {
        return $this->getRepository()->findOneBy(['id' => $labelId]);
    }

    public function findVisibleLabelByLanguage($labelLanguage)
    {
        $objectRepository = $this->getRepository(); // From clause is integrated in this statement
        $qb = $objectRepository->createQueryBuilder('c');
        $qb->select('c')->where('c.language=:lang');
        $qb->setParameter('lang', $labelLanguage);
        $labels = $qb->getQuery()->getResult();

        return $labels;
    }

    /**
     * Find the same label, but in the given language
     *
     */
    public function siblingLabel($label, $lang)
    {
        $objectRepository = $this->getRepository(); // From clause is integrated in this statement
        $qb = $objectRepository->createQueryBuilder('c');
        $qb->select('c')
            ->where('c.languageNeutralId=:labelId')
            ->andWhere('c.language=:language');
        $qb->setParameter('labelId', $label->getLanguageNeutralId());
        $qb->setParameter('language', $lang);

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function findAllLabelsById($labelId)
    {
        $objectRepository = $this->getRepository(); // From clause is integrated in this statement
        $qb = $objectRepository->createQueryBuilder('c');
        $qb->select('c')->where('c.languageNeutralId=:labelId');
        $qb->setParameter('labelId', $labelId);
        $labels = $qb->getQuery()->getResult();

        return $labels;
    }

    /**
     * Deletes the given label
     *
     * @param LabelModel $label
     */
    public function delete($label)
    {
        $this->em->remove($label);
        $this->em->flush();
    }

    /**
     * Deletes the given label
     *
     * @param int $labelId
     */
    public function deleteById($labelId)
    {
        $label = $this->findEditableLabel($labelId);
        if (is_null($label)) {
            return;
        }

        $this->delete($label);
    }

    /**
     * Find all Labels.
     *
     * @return array
     */
    public function findAll()
    {
        return $this->getRepository()->findAll();
    }

    /**
     * Get the repository for this mapper.
     *
     * @return Doctrine\ORM\EntityRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('Company\Model\JobLabel');
    }
}
