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

    /**
     * Saves all labels
     *
     */
    public function save()
    {
        $this->em->flush();
    }

    /**
     * Finds the label with the given id
     *
     * @param integer $packageID
     */
    public function findLabel($labelSlug)
    {
        return $this->getRepository()->findOneBy(['slug' => $labelSlug]);
    }

    public function findVisibleLabelByLanguage($labelLanguage)
    {
        $objectRepository = $this->getRepository(); // From clause is integrated in this statement
        $qb = $objectRepository->createQueryBuilder('c');
        $qb->select('c')->where('c.language=:lang');
        $qb->andWhere('c.hidden=:hidden');
        $qb->setParameter('lang', $labelLanguage);
        $qb->setParameter('hidden', false);
        $labels = $qb->getQuery()->getResult();

        return $labels;
    }

    public function createNullLabel($lang, $translator)
    {
        $labelForJobsWithoutLabel =  new LabelModel();
        $labelForJobsWithoutLabel->setHidden(false);
        $labelForJobsWithoutLabel->setLanguageNeutralId(null);
        $labelForJobsWithoutLabel->setLanguage($lang);
        $labelForJobsWithoutLabel->setSlug("jobs");
        $labelForJobsWithoutLabel->setName($translator->translate("Job"));

        return $labelForJobsWithoutLabel;
    }

    /**
     * Find the same label, but in the given language
     *
     */
    public function siblingLabel($label, $lang)
    {
        $objectRepository = $this->getRepository(); // From clause is integrated in this statement
        $qb = $objectRepository->createQueryBuilder('c');
        $qb->select('c')->where('c.languageNeutralId=:labelID')->andWhere('c.language=:language');
        $qb->setParameter('labelID', $label->getLanguageNeutralId());
        $qb->setParameter('language', $lang);
        $labels = $qb->getQuery()->getResult();
        return $labels[0];
    }

    public function findAllLabelsById($labelId)
    {
        $objectRepository = $this->getRepository(); // From clause is integrated in this statement
        $qb = $objectRepository->createQueryBuilder('c');
        $qb->select('c')->where('c.languageNeutralId=:labelID');
        $qb->setParameter('labelID', $labelId);
        $labels = $qb->getQuery()->getResult();

        return $labels;
    }
    /**
     * Deletes the given label
     *
     */
    public function delete($labelID)
    {
        $label = $this->findEditableLabel($labelID);
        if (is_null($label)) {
            return;
        }

        $this->em->remove($label);
        $this->em->flush();
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
